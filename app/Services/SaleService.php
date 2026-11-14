<?php

namespace App\Services;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SaleService
{
    public function __construct(private StockService $stock) {}

    /**
     * @param array $items    [['product_id' => 1, 'qty' => 2], ...]
     * @param array $payments ['cash' => 5000, 'card' => 0, 'transfer' => 0]  (cash = o que o cliente entregou)
     */
    public function create(User $user, array $items, array $payments, float $discount = 0): Sale
    {
        if (! $user->store_id) {
            throw new DomainException('Só utilizadores de uma loja registam vendas.');
        }
        if ($discount > 0 && ! Gate::forUser($user)->allows('sales.discount')) {
            throw new DomainException('Não tens permissão para aplicar desconto.');
        }

        // junta linhas repetidas do mesmo produto
        $lines = [];
        foreach ($items as $i) {
            $qty = round((float) ($i['qty'] ?? 0), 3);
            if ($qty <= 0) {
                throw new DomainException('Há uma quantidade inválida no carrinho.');
            }
            $pid = (int) $i['product_id'];
            $lines[$pid] = round(($lines[$pid] ?? 0) + $qty, 3);
        }
        if (! $lines) {
            throw new DomainException('O carrinho está vazio.');
        }
        ksort($lines); // ordem fixa evita bloqueios cruzados entre vendas simultâneas

        return DB::transaction(function () use ($user, $lines, $payments, $discount) {
            $session = CashSession::withoutGlobalScopes()
                ->where('user_id', $user->id)->where('status', 'open')
                ->lockForUpdate()->first();

            if (! $session) {
                throw new DomainException('Abre o teu turno de caixa antes de vender.');
            }

            $products = Product::withoutGlobalScopes()
                ->where('store_id', $user->store_id)
                ->whereIn('id', array_keys($lines))->get()->keyBy('id');

            // ---- linhas (em cêntimos, para não haver erros de arredondamento) ----
            $rows = [];
            $subtotal = 0;
            foreach ($lines as $pid => $qty) {
                $p = $products->get($pid);
                if (! $p || ! $p->is_active) {
                    throw new DomainException('Há um produto indisponível no carrinho.');
                }
                if (! $p->allow_decimal && floor($qty) != $qty) {
                    throw new DomainException("«{$p->name}» só se vende em unidades inteiras.");
                }

                $priceCents = (int) round($p->sale_price * 100);
                $lineCents  = (int) round($qty * $priceCents);
                $subtotal  += $lineCents;

                $rows[] = [
                    'product' => $p,
                    'qty'     => $qty,
                    'price'   => $priceCents / 100,
                    'cost'    => (float) $p->cost_price,
                    'line'    => $lineCents / 100,
                ];
            }

            $discountCents = (int) round($discount * 100);
            if ($discountCents < 0 || $discountCents > $subtotal) {
                throw new DomainException('O desconto é inválido.');
            }
            $total = $subtotal - $discountCents;
            if ($total <= 0) {
                throw new DomainException('O total da venda tem de ser maior que zero.');
            }

            // ---- pagamentos ----
            $cash     = (int) round(max(0, (float) ($payments['cash'] ?? 0)) * 100);
            $card     = (int) round(max(0, (float) ($payments['card'] ?? 0)) * 100);
            $transfer = (int) round(max(0, (float) ($payments['transfer'] ?? 0)) * 100);

            $nonCash = $card + $transfer;
            if ($nonCash > $total) {
                throw new DomainException('Cartão e transferência não podem ser maiores que o total.');
            }

            $cashDue = $total - $nonCash;
            if ($cash < $cashDue) {
                throw new DomainException('Pagamento insuficiente. Faltam '.number_format(($cashDue - $cash) / 100, 2, ',', '.').'.');
            }
            if ($cashDue === 0 && $cash > 0) {
                throw new DomainException('A venda já está paga sem dinheiro. Retira o valor em dinheiro.');
            }
            $change = $cashDue > 0 ? $cash - $cashDue : 0;

            // ---- número sequencial por loja ----
            $store = Store::lockForUpdate()->findOrFail($user->store_id);
            $seq   = (int) Sale::withoutGlobalScopes()->where('store_id', $store->id)->max('seq') + 1;
            $number = 'LJ'.$store->id.'-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'store_id'        => $store->id,
                'cash_session_id' => $session->id,
                'user_id'         => $user->id,
                'seq'             => $seq,
                'number'          => $number,
                'subtotal'        => $subtotal / 100,
                'discount'        => $discountCents / 100,
                'total'           => $total / 100,
                'status'          => 'completed',
            ]);

            foreach ($rows as $r) {
                $sale->items()->create([
                    'product_id'   => $r['product']->id,
                    'product_name' => $r['product']->name,
                    'qty'          => $r['qty'],
                    'unit_price'   => $r['price'],
                    'unit_cost'    => $r['cost'],
                    'subtotal'     => $r['line'],
                ]);

                try {
                    $this->stock->out(
                        $store->id, $r['product']->id, MovementType::Sale,
                        $r['qty'], $sale, $r['cost'], 'Venda '.$number
                    );
                } catch (InsufficientStockException $e) {
                    throw new DomainException("Estoque insuficiente para «{$r['product']->name}».");
                }
            }

            if ($cashDue > 0) {
                $sale->payments()->create([
                    'method' => 'cash', 'amount' => $cashDue / 100,
                    'tendered' => $cash / 100, 'change_given' => $change / 100,
                ]);
            }
            if ($card > 0) {
                $sale->payments()->create(['method' => 'card', 'amount' => $card / 100]);
            }
            if ($transfer > 0) {
                $sale->payments()->create(['method' => 'transfer', 'amount' => $transfer / 100]);
            }

            return $sale;
        });
    }

    public function cancel(Sale $sale, User $user, string $reason): Sale
{
    return DB::transaction(function () use ($sale, $user, $reason) {
        $s = Sale::withoutGlobalScopes()->lockForUpdate()->with('items')->findOrFail($sale->id);

        if (! $user->is_super_admin && $s->store_id !== $user->store_id) {
            throw new DomainException('Venda inválida.');
        }
        if ($s->status !== 'completed') {
            throw new DomainException('Esta venda já foi cancelada.');
        }

        $session = CashSession::withoutGlobalScopes()->lockForUpdate()->find($s->cash_session_id);
        if (! $session || $session->status !== 'open') {
            throw new DomainException('O turno desta venda já foi fechado. Já não é possível cancelá-la.');
        }

        // ordem fixa, como na venda, para evitar bloqueios cruzados
        foreach ($s->items->sortBy('product_id') as $item) {
            $this->stock->in(
                $s->store_id, $item->product_id, MovementType::Cancel,
                (float) $item->qty, $s, (float) $item->unit_cost,
                'Cancelamento '.$s->number
            );
        }

        $s->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancelled_by'  => $user->id,
            'cancel_reason' => $reason,
        ]);

        return $s;
    });
}
}
