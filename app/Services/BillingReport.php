<?php

namespace App\Services;

use App\Models\BillingReceipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BillingReport
{
    public function normalize(array $f): array
    {
        foreach (['from', 'to'] as $k) {
            try {
                $f[$k] = Carbon::parse(($f[$k] ?? null) ?: 'today')->toDateString();
            } catch (\Throwable) {
                $f[$k] = today()->toDateString();
            }
        }
        if ($f['from'] > $f['to']) {
            [$f['from'], $f['to']] = [$f['to'], $f['from']];
        }

        return $f;
    }

    /** Alocações (quanto de cada recibo foi para cada cobrança), com o contrato ligado. */
    private function allocQuery()
    {
        return DB::table('charge_allocations as ca')
            ->join('charges as c', 'c.id', '=', 'ca.charge_id')
            ->leftJoin('subscriptions as s', 's.id', '=', 'c.subscription_id');
    }

    /** Recibos da loja que cumprem todos os filtros. */
    public function base(array $f): Builder
    {
        $f     = $this->normalize($f);
        $store = (int) $f['store_id'];

        return BillingReceipt::withoutGlobalScopes()
            ->where('billing_receipts.store_id', $store)
            ->whereBetween('billing_receipts.created_at', [
                Carbon::parse($f['from'])->startOfDay(), Carbon::parse($f['to'])->endOfDay(),
            ])
            ->when(! empty($f['user_id']), fn ($q) => $q->where('billing_receipts.user_id', (int) $f['user_id']))
            ->when(! empty($f['q']), function ($q) use ($f, $store) {
                $t = '%'.trim($f['q']).'%';
                $q->whereIn('billing_receipts.customer_id', DB::table('customers')
                    ->where('store_id', $store)
                    ->where(fn ($w) => $w->where('name', 'like', $t)->orWhere('block', 'like', $t)
                        ->orWhere('unit', 'like', $t)->orWhere('phone', 'like', $t))
                    ->select('id'));
            })
            ->when(! empty($f['service_id']), fn ($q) => $q->whereIn('billing_receipts.id',
                $this->allocQuery()->where('s.service_id', (int) $f['service_id'])->select('ca.billing_receipt_id')))
            ->when(! empty($f['method']), fn ($q) => $q->whereIn('billing_receipts.id',
                DB::table('billing_receipt_payments')->where('method', $f['method'])->select('billing_receipt_id')))
            ->when(! empty($f['status']), fn ($q) => $q->where('billing_receipts.status', $f['status']));
    }

    private function done(array $f): array
    {
        return array_merge($f, ['status' => 'completed']);
    }

    public function totals(array $f): array
    {
        $done = $this->done($f);

        $r = $this->base($done)
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(billing_receipts.total),0) as total')->first();

        $c = $this->base(array_merge($f, ['status' => 'cancelled']))
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(billing_receipts.total),0) as total')->first();

        $collected = (float) $r->total;

        // com filtro de serviço, conta só a parte desse serviço
        if (! empty($f['service_id'])) {
            $collected = (float) $this->allocQuery()
                ->whereIn('ca.billing_receipt_id', $this->base($done)->select('billing_receipts.id'))
                ->where('s.service_id', (int) $f['service_id'])
                ->sum('ca.amount');
        }

        $n = (int) $r->n;

        return [
            'count'           => $n,
            'collected'       => round($collected, 2),
            'avg'             => $n ? round($collected / $n, 2) : 0,
            'cancelled'       => (int) $c->n,
            'cancelled_total' => (float) $c->total,
        ];
    }

    /** Dívida em atraso neste momento (não depende dos filtros). */
    public function overdue(int $storeId): object
    {
        return DB::table('charges')
            ->where('store_id', $storeId)->where('status', 'pending')
            ->whereDate('due_date', '<', today())
            ->selectRaw('COUNT(DISTINCT customer_id) as customers, COALESCE(SUM(amount - paid_amount),0) as owed')
            ->first();
    }

    public function byMethod(array $f)
    {
        return DB::table('billing_receipt_payments as p')
            ->whereIn('p.billing_receipt_id', $this->base($this->done($f))->select('billing_receipts.id'))
            ->when(! empty($f['method']), fn ($q) => $q->where('p.method', $f['method']))
            ->groupBy('p.method')
            ->selectRaw('p.method, SUM(p.amount) as amount')
            ->pluck('amount', 'method');
    }

    public function byCollector(array $f)
    {
        $rows = $this->base($this->done($f))
            ->groupBy('billing_receipts.user_id')
            ->selectRaw('billing_receipts.user_id, COUNT(*) as n, SUM(billing_receipts.total) as total')
            ->orderByDesc('total')->get();

        $names = User::whereIn('id', $rows->pluck('user_id'))->pluck('name', 'id');

        return $rows->map(fn ($r) => (object) [
            'name' => $names[$r->user_id] ?? '-', 'n' => (int) $r->n, 'total' => (float) $r->total,
        ]);
    }

    public function byService(array $f)
    {
        return $this->allocQuery()
            ->leftJoin('services as sv', 'sv.id', '=', 's.service_id')
            ->whereIn('ca.billing_receipt_id', $this->base($this->done($f))->select('billing_receipts.id'))
            ->when(! empty($f['service_id']), fn ($q) => $q->where('s.service_id', (int) $f['service_id']))
            ->groupBy('sv.id', 'sv.name')
            ->selectRaw("COALESCE(sv.name, 'Outros') as name, COUNT(DISTINCT ca.billing_receipt_id) as n, SUM(ca.amount) as amount")
            ->orderByDesc('amount')->get();
    }

    public function byDay(array $f)
    {
        return $this->base($this->done($f))
            ->groupBy('d')
            ->selectRaw('DATE(billing_receipts.created_at) as d, COUNT(*) as n, SUM(billing_receipts.total) as total')
            ->orderBy('d')->get();
    }
}
