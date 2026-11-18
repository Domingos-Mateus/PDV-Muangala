<?php

namespace App\Services;

use App\Models\BillingReceipt;
use App\Models\CashSession;
use App\Models\Charge;
use App\Models\ChargeAllocation;
use App\Models\Customer;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class BillingService
{
    private const MONTHS = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    // ---------- períodos ----------

    /** Início do período número $k, sempre contado a partir da data de início (sem "deriva" nos meses de 28/30/31 dias). */
    private function periodStart(Subscription $s, int $k): Carbon
    {
        $a = $s->start_date->copy()->startOfDay();
        $n = $k * max(1, (int) $s->interval_count);

        return match ($s->frequency) {
            'daily'   => $a->addDays($n),
            'weekly'  => $a->addDays($n * 7),
            default   => $a->addMonthsNoOverflow($n),
        };
    }

    private function indexOf(Subscription $s, Carbon $date): int
    {
        $a = $s->start_date->copy()->startOfDay();
        $d = $date->copy()->startOfDay();
        $i = max(1, (int) $s->interval_count);

        return match ($s->frequency) {
            'daily'  => intdiv((int) $a->diffInDays($d), $i),
            'weekly' => intdiv((int) $a->diffInDays($d), $i * 7),
            default  => intdiv(($d->year - $a->year) * 12 + ($d->month - $a->month), $i),
        };
    }

    private function nextStart(Subscription $s, Carbon $start): Carbon
    {
        return $this->periodStart($s, $this->indexOf($s, $start) + 1);
    }

    private function label(Subscription $s, Carbon $start, Carbon $end): string
    {
        $name = $s->service->name;

        if ($s->frequency === 'monthly' && (int) $s->interval_count === 1) {
            return $name.' · '.self::MONTHS[$start->month - 1].'/'.$start->year;
        }
        if ($s->frequency === 'daily' && (int) $s->interval_count === 1) {
            return $name.' · '.$start->format('d/m/Y');
        }

        return $name.' · '.$start->format('d/m/Y').' a '.$end->format('d/m/Y');
    }

    // ---------- gerar cobranças ----------

    /**
     * Gera as cobranças em atraso e as do período actual.
     * $ahead = quantos períodos futuros gerar além disso (máximo 12), para pagamento adiantado.
     * Pode correr várias vezes: o índice único (contrato + início do período) impede duplicados.
     */
    public function generate(Subscription $sub, int $ahead = 0): int
    {
        return DB::transaction(function () use ($sub, $ahead) {
            $s = Subscription::withoutGlobalScopes()->lockForUpdate()->with('service')->findOrFail($sub->id);

            if ($s->status !== 'active') {
                return 0;
            }

            $created = 0;
            $extra   = max(0, min($ahead, 12));
            $today   = today();
            $guard   = 0;

            while ($guard++ < 600) {
                $start = $s->next_charge_date->copy();

                if ($s->end_date && $start->gt($s->end_date)) {
                    break;
                }
                if ($start->gt($today)) {
                    if ($extra <= 0) {
                        break;
                    }
                    $extra--;
                }

                $next = $this->nextStart($s, $start);
                $end  = $next->copy()->subDay();

                $charge = Charge::withoutGlobalScopes()->firstOrCreate(
                    ['subscription_id' => $s->id, 'period_start' => $start->toDateString()],
                    [
                        'store_id'    => $s->store_id,
                        'customer_id' => $s->customer_id,
                        'description' => $this->label($s, $start, $end),
                        'period_end'  => $end->toDateString(),
                        'due_date'    => $end->toDateString(),
                        'amount'      => $s->price,
                        'paid_amount' => 0,
                        'status'      => 'pending',
                    ]
                );
                if ($charge->wasRecentlyCreated) {
                    $created++;
                }

                $s->next_charge_date = $next;
            }

            $s->save();

            return $created;
        });
    }

    // ---------- receber ----------

    /**
     * @param array $allocations [charge_id => valor a pagar]
     * @param array $payments    ['cash' => entregue, 'card' => 0, 'transfer' => 0]
     */
    public function receive(User $user, Customer $customer, array $allocations, array $payments): BillingReceipt
    {
        if (! $user->store_id) {
            throw new DomainException('Só utilizadores de uma loja recebem pagamentos.');
        }

        $wanted = [];
        foreach ($allocations as $id => $amount) {
            $cents = (int) round(((float) $amount) * 100);
            if ($cents > 0) {
                $wanted[(int) $id] = $cents;
            }
        }
        if (! $wanted) {
            throw new DomainException('Escolhe pelo menos um período para pagar.');
        }
        ksort($wanted);

        return DB::transaction(function () use ($user, $customer, $wanted, $payments) {
            $session = CashSession::withoutGlobalScopes()
                ->where('user_id', $user->id)->where('status', 'open')
                ->lockForUpdate()->first();

            if (! $session) {
                throw new DomainException('Abre o teu turno de caixa antes de receber.');
            }
            if ($customer->store_id !== $user->store_id) {
                throw new DomainException('Cliente inválido.');
            }

            $charges = Charge::withoutGlobalScopes()
                ->where('store_id', $user->store_id)->where('customer_id', $customer->id)
                ->whereIn('id', array_keys($wanted))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($charges->count() !== count($wanted)) {
                throw new DomainException('Há cobranças que não pertencem a este cliente.');
            }

            // ---- valida cada linha ----
            $total = 0;
            foreach ($wanted as $id => $cents) {
                $c = $charges[$id];

                if ($c->status !== 'pending') {
                    throw new DomainException("«{$c->description}» já não está pendente.");
                }

                $balance = (int) round((float) $c->amount * 100) - (int) round((float) $c->paid_amount * 100);

                if ($cents > $balance) {
                    throw new DomainException("O valor de «{$c->description}» é maior que o saldo.");
                }
                // só a dívida (já vencida) se pode pagar em parcelas
                               // só a dívida (mês que já passou) se pode pagar em parcelas
                if ($cents < $balance && ! $c->due_date->lt(today())) {
                    throw new DomainException("«{$c->description}» ainda não está em atraso e tem de ser paga por inteiro.");
                }

                $total += $cents;
            }

            // ---- pagamento (mesmas regras das vendas) ----
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
                throw new DomainException('O recibo já está pago sem dinheiro. Retira o valor em dinheiro.');
            }
            $change = $cashDue > 0 ? $cash - $cashDue : 0;

            // ---- número sequencial por loja ----
            $store  = Store::lockForUpdate()->findOrFail($user->store_id);
            $seq    = (int) BillingReceipt::withoutGlobalScopes()->where('store_id', $store->id)->max('seq') + 1;
            $number = 'RC'.$store->id.'-'.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);

            $receipt = BillingReceipt::create([
                'store_id'        => $store->id,
                'cash_session_id' => $session->id,
                'user_id'         => $user->id,
                'customer_id'     => $customer->id,
                'seq'             => $seq,
                'number'          => $number,
                'total'           => $total / 100,
                'status'          => 'completed',
            ]);

            foreach ($wanted as $id => $cents) {
                $c = $charges[$id];

                ChargeAllocation::create([
                    'billing_receipt_id' => $receipt->id,
                    'charge_id'          => $c->id,
                    'amount'             => $cents / 100,
                ]);

                $paid = (int) round((float) $c->paid_amount * 100) + $cents;
                $c->update([
                    'paid_amount' => $paid / 100,
                    'status'      => $paid >= (int) round((float) $c->amount * 100) ? 'paid' : 'pending',
                ]);
            }

            if ($cashDue > 0) {
                $receipt->payments()->create([
                    'method' => 'cash', 'amount' => $cashDue / 100,
                    'tendered' => $cash / 100, 'change_given' => $change / 100,
                ]);
            }
            if ($card > 0) {
                $receipt->payments()->create(['method' => 'card', 'amount' => $card / 100]);
            }
            if ($transfer > 0) {
                $receipt->payments()->create(['method' => 'transfer', 'amount' => $transfer / 100]);
            }

            return $receipt;
        });
    }

    // ---------- cancelar ----------

    public function cancel(BillingReceipt $receipt, User $user, string $reason): BillingReceipt
    {
        return DB::transaction(function () use ($receipt, $user, $reason) {
            $r = BillingReceipt::withoutGlobalScopes()->lockForUpdate()->with('allocations')->findOrFail($receipt->id);

            if (! $user->is_super_admin && $r->store_id !== $user->store_id) {
                throw new DomainException('Recibo inválido.');
            }
            if ($r->status !== 'completed') {
                throw new DomainException('Este recibo já foi cancelado.');
            }

            $session = CashSession::withoutGlobalScopes()->lockForUpdate()->find($r->cash_session_id);
            if (! $session || $session->status !== 'open') {
                throw new DomainException('O turno deste recibo já foi fechado. Já não é possível cancelá-lo.');
            }

            foreach ($r->allocations->sortBy('charge_id') as $a) {
                $c = Charge::withoutGlobalScopes()->lockForUpdate()->find($a->charge_id);
                if (! $c) {
                    continue;
                }
                $paid = max(0, (int) round((float) $c->paid_amount * 100) - (int) round((float) $a->amount * 100));
                $c->update(['paid_amount' => $paid / 100, 'status' => 'pending']);
            }

            $r->update([
                'status'        => 'cancelled',
                'cancelled_at'  => now(),
                'cancelled_by'  => $user->id,
                'cancel_reason' => $reason,
            ]);

            return $r;
        });
    }
}
