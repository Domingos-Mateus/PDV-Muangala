<?php

namespace App\Livewire;

use App\Models\CashSession;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Services\SaleService;
use DomainException;
use Livewire\Component;

class Pdv extends Component
{

    public string $search = '';
    public array $cart = [];
    public $discount = '';
    public $cash = '';
    public $card = '';
    public $transfer = '';
    public ?int $lastSaleId = null;
    public ?string $lastSaleNumber = null;

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->store_id, 403);
        abort_unless(
            CashSession::where('user_id', $user->id)->where('status', 'open')->exists(),
            403, 'Abre o teu turno de caixa antes de vender.'
        );
    }

    // ---------- produtos ----------

    public function scan(string $term = ''): void
    {
        $term = trim($term);
        if ($term === '') {
            return;
        }

        $product = Product::where('is_active', true)
            ->where(fn ($q) => $q->where('barcode', $term)->orWhere('sku', $term))
            ->first();

        if (! $product) {
            $matches = Product::where('is_active', true)->where('name', 'like', "%{$term}%")->limit(2)->get();
            if ($matches->count() === 1) {
                $product = $matches->first();
            }
        }

        if (! $product) {
            $this->notify('error', 'Produto não encontrado', 'Escolhe um da lista ou confirma o código.');
            return;
        }

        $this->add($product->id);
    }

    public function add(int $id): void
    {
        $p = Product::where('is_active', true)->find($id);
        if (! $p) {
            return;
        }

        $stock   = $this->stockOf($id);
        $current = (float) ($this->cart[$id]['qty'] ?? 0);
        $new     = $p->allow_decimal ? min($current + 1, $stock) : $current + 1;

        if ($stock <= 0 || $new > $stock || $new <= $current) {
            $this->notify('error', 'Sem estoque suficiente', "{$p->name}: disponível {$stock} {$p->unit}.");
            return;
        }

        $this->cart[$id] = [
            'name'          => $p->name,
            'unit'          => $p->unit,
            'price'         => (float) $p->sale_price,
            'qty'           => $new,
            'allow_decimal' => (bool) $p->allow_decimal,
        ];

        $this->search = '';
        $this->dispatch('pdv-focus');
    }

    public function inc(int $id): void
    {
        if (isset($this->cart[$id])) {
            $this->add($id);
        }
    }

    public function dec(int $id): void
    {
        if (! isset($this->cart[$id])) {
            return;
        }
        $new = round($this->num($this->cart[$id]['qty']) - 1, 3);
        if ($new <= 0) {
            unset($this->cart[$id]);
        } else {
            $this->cart[$id]['qty'] = $new;
        }
    }

    public function remove(int $id): void
    {
        unset($this->cart[$id]);
    }

    public function updatedCart($value, $key): void
    {
        [$id, $field] = array_pad(explode('.', (string) $key), 2, null);
        if ($field !== 'qty' || ! isset($this->cart[$id])) {
            return;
        }

        $item  = $this->cart[$id];
        $qty   = $this->num($item['qty']);
        $qty   = $item['allow_decimal'] ? round($qty, 3) : floor($qty);
        $stock = $this->stockOf((int) $id);

        if ($qty <= 0) {
            unset($this->cart[$id]);
            return;
        }
        if ($qty > $stock) {
            $qty = $stock;
            $this->notify('error', 'Sem estoque suficiente', "{$item['name']}: disponível {$stock} {$item['unit']}.");
            if ($qty <= 0) {
                unset($this->cart[$id]);
                return;
            }
        }

        $this->cart[$id]['qty'] = $qty;
    }

    // ---------- pagamento ----------

    public function exact(): void
    {
        $t = $this->totals();
        $this->cash = $t['cashDue'] > 0 ? number_format($t['cashDue'], 2, '.', '') : '';
    }

    public function checkout(SaleService $sales): void
    {
        $user = auth()->user();
        abort_unless($user->can('sales.create'), 403);

        if (! $this->cart) {
            $this->notify('error', 'Carrinho vazio', 'Adiciona produtos antes de finalizar.');
            return;
        }

        $items = [];
        foreach ($this->cart as $id => $i) {
            $items[] = ['product_id' => $id, 'qty' => $this->num($i['qty'])];
        }

        try {
            $sale = $sales->create(
                $user, $items,
                ['cash' => $this->num($this->cash), 'card' => $this->num($this->card), 'transfer' => $this->num($this->transfer)],
                $user->can('sales.discount') ? $this->num($this->discount) : 0
            );
        } catch (DomainException $e) {
            $this->notify('error', 'Não foi possível finalizar', $e->getMessage());
            return;
        }

        $change = (float) $sale->payments()->where('method', 'cash')->value('change_given');

        $this->lastSaleId     = $sale->id;
        $this->lastSaleNumber = $sale->number;
        $this->reset('cart', 'discount', 'cash', 'card', 'transfer', 'search');

        $this->dispatch('pdv-sale-done',
    number: $sale->number,
    total: number_format((float) $sale->total, 2, ',', '.'),
    change: number_format($change, 2, ',', '.'),
    url: route('sales.receipt', [$sale->id, 'auto' => 1])
);
        $this->dispatch('pdv-focus');
    }

    // ---------- auxiliares ----------

    private function num($v): float
    {
        $v = str_replace(',', '.', trim((string) $v));
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private function stockOf(int $productId): float
    {
        return (float) ProductStock::where('product_id', $productId)->value('qty');
    }

    private function notify(string $type, string $title, string $text = ''): void
    {
        $this->dispatch('pdv-notify', type: $type, title: $title, text: $text);
    }

    private function totals(): array
    {
        $subtotal = 0.0;
        foreach ($this->cart as $i) {
            $subtotal += round($this->num($i['qty']) * $i['price'], 2);
        }
        $subtotal = round($subtotal, 2);

        $discount = auth()->user()->can('sales.discount')
            ? min(max($this->num($this->discount), 0), $subtotal) : 0.0;
        $total    = round($subtotal - $discount, 2);

        $card     = $this->num($this->card);
        $transfer = $this->num($this->transfer);
        $cash     = $this->num($this->cash);

        $over    = round($card + $transfer, 2) > $total;
        $cashDue = round($total - $card - $transfer, 2);
        $change  = $cashDue > 0 ? max(0, round($cash - $cashDue, 2)) : 0;
        $missing = $cashDue > 0 ? max(0, round($cashDue - $cash, 2)) : 0;

        $blocked = $total <= 0 || $over || $missing > 0 || ($cashDue <= 0 && $cash > 0);

        return compact('subtotal', 'discount', 'total', 'cashDue', 'change', 'missing', 'over', 'blocked');
    }

    public function render()
    {
        $term    = trim($this->search);
        $results = collect();

        if (mb_strlen($term) >= 2) {
            $results = Product::with('stock')->where('is_active', true)
                ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%"))
                ->orderBy('name')->limit(8)->get();
        }

        return view('livewire.pdv', [
    'results' => $results,
    't'       => $this->totals(),
    'recent'  => Sale::where('user_id', auth()->id())->latest('id')->limit(8)->get(),
]);
    }
}
