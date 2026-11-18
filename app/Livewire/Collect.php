<?php

namespace App\Livewire;

use App\Models\BillingReceipt;
use App\Models\CashSession;
use App\Models\Charge;
use App\Models\Customer;
use App\Models\Subscription;
use App\Services\BillingService;
use DomainException;
use Livewire\Component;

class Collect extends Component
{
    public string $search = '';
    public ?int $customerId = null;
    public array $selected = [];
    public array $amounts = [];
    public int $ahead = 0;
    public $cash = '';
    public $card = '';
    public $transfer = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->store_id, 403);
        abort_unless(
            CashSession::where('user_id', $user->id)->where('status', 'open')->exists(),
            403, 'Abre o teu turno de caixa antes de receber.'
        );
    }

    // ---------- cliente ----------

    public function pick(int $id, BillingService $billing): void
    {
        $customer = Customer::where('is_active', true)->find($id);
        if (! $customer) {
            return;
        }

        $this->customerId = $customer->id;
        $this->search = '';
        $this->ahead = 0;
        $this->resetSelection();
        $this->generate($customer, 0, $billing);
    }

    public function clear(): void
    {
        $this->customerId = null;
        $this->ahead = 0;
        $this->resetSelection();
    }

    /** Gera mais um período à frente em cada contrato (pagamento adiantado). */
    public function more(BillingService $billing): void
    {
        $customer = $this->customerId ? Customer::find($this->customerId) : null;
        if (! $customer || $this->ahead >= 12) {
            return;
        }

        $this->ahead++;
        $this->generate($customer, 1, $billing);
    }

    private function generate(Customer $c, int $ahead, BillingService $billing): void
    {
        Subscription::where('customer_id', $c->id)->where('status', 'active')
            ->each(fn (Subscription $s) => $billing->generate($s, $ahead));
    }

    private function resetSelection(): void
    {
        $this->selected = [];
        $this->amounts = [];
        $this->cash = $this->card = $this->transfer = '';
    }

    // ---------- selecção ----------

    public function updatedSelected($value, $key): void
    {
        $id = (int) $key;
        $c = Charge::where('customer_id', $this->customerId)->where('status', 'pending')->find($id);

        if ($value && $c) {
            $this->amounts[$id] = number_format($c->balance(), 2, '.', '');
        } else {
            unset($this->amounts[$id], $this->selected[$id]);
        }
    }

    private function pending()
    {
        return $this->customerId
            ? Charge::where('customer_id', $this->customerId)->where('status', 'pending')->orderBy('period_start')->get()->keyBy('id')
            : collect();
    }

    /** O valor de cada linha: só a dívida em atraso pode ser parcial. */
    private function allocations($charges): array
    {
        $out = [];
        foreach ($this->selected as $id => $on) {
            if (! $on || ! isset($charges[$id])) {
                continue;
            }
            $c = $charges[$id];
            $balance = $c->balance();
            $amount = $c->due_date->lt(today())
                ? min(max($this->num($this->amounts[$id] ?? $balance), 0), $balance)
                : $balance;

            if ($amount > 0) {
                $out[(int) $id] = round($amount, 2);
            }
        }
        return $out;
    }

    // ---------- pagamento ----------

    public function exact(): void
    {
        $t = $this->totals($this->allocations($this->pending()));
        $this->cash = $t['cashDue'] > 0 ? number_format($t['cashDue'], 2, '.', '') : '';
    }

    public function checkout(BillingService $billing): void
    {
        $user = auth()->user();
        abort_unless($user->can('billing.collect'), 403);

        $customer = $this->customerId ? Customer::find($this->customerId) : null;
        $alloc = $this->allocations($this->pending());

        if (! $customer || ! $alloc) {
            $this->notify('Nada para receber', 'Escolhe o cliente e marca os períodos a pagar.');
            return;
        }

        try {
            $receipt = $billing->receive($user, $customer, $alloc, [
                'cash' => $this->num($this->cash), 'card' => $this->num($this->card), 'transfer' => $this->num($this->transfer),
            ]);
        } catch (DomainException $e) {
            $this->notify('Não foi possível receber', $e->getMessage());
            return;
        }

        $change = (float) $receipt->payments()->where('method', 'cash')->value('change_given');
        $this->resetSelection();

        $this->dispatch('collect-done',
            number: $receipt->number,
            total: number_format((float) $receipt->total, 2, ',', '.'),
            change: number_format($change, 2, ',', '.'),
            url: route('billing.receipt', [$receipt->id, 'auto' => 1])
        );
    }

    // ---------- auxiliares ----------

    private function num($v): float
    {
        $v = str_replace(',', '.', trim((string) $v));
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private function notify(string $title, string $text): void
    {
        $this->dispatch('collect-notify', title: $title, text: $text);
    }

    private function totals(array $alloc): array
    {
        $total = round(array_sum($alloc), 2);
        $card = $this->num($this->card);
        $transfer = $this->num($this->transfer);
        $cash = $this->num($this->cash);

        $over = round($card + $transfer, 2) > $total;
        $cashDue = round($total - $card - $transfer, 2);
        $change = $cashDue > 0 ? max(0, round($cash - $cashDue, 2)) : 0;
        $missing = $cashDue > 0 ? max(0, round($cashDue - $cash, 2)) : 0;
        $blocked = $total <= 0 || $over || $missing > 0 || ($cashDue <= 0 && $cash > 0);

        return compact('total', 'cashDue', 'change', 'missing', 'over', 'blocked');
    }

    public function render()
    {
        $term = trim($this->search);
        $results = collect();

        if (mb_strlen($term) >= 2) {
            $results = Customer::where('is_active', true)
                ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('block', 'like', "%{$term}%")->orWhere('unit', 'like', "%{$term}%"))
                ->orderBy('name')->limit(8)->get();
        }

        $charges = $this->pending();
        $alloc = $this->allocations($charges);

        return view('livewire.collect', [
            'results'  => $results,
            'customer' => $this->customerId ? Customer::find($this->customerId) : null,
            'charges'  => $charges,
            'alloc'    => $alloc,
            't'        => $this->totals($alloc),
            'recent'   => BillingReceipt::where('user_id', auth()->id())->with('customer:id,name')->latest('id')->limit(6)->get(),
        ]);
    }
}
