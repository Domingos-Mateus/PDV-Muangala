<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class SalesReport
{
    public function normalize(array $f): array
    {
        foreach (['from', 'to'] as $k) {
            try {
                $f[$k] = Carbon::parse($f[$k] ?? null ?: 'today')->toDateString();
            } catch (\Throwable) {
                $f[$k] = today()->toDateString();
            }
        }
        if ($f['from'] > $f['to']) {
            [$f['from'], $f['to']] = [$f['to'], $f['from']];
        }
        return $f;
    }

    /** Query base com todos os filtros. O funcionário só vê as suas vendas. */
    public function base(array $f, User $user): Builder
    {
        $f = $this->normalize($f);

        return Sale::withoutGlobalScopes()
            ->where('sales.store_id', $f['store_id'])
            ->whereBetween('sales.created_at', [
                Carbon::parse($f['from'])->startOfDay(), Carbon::parse($f['to'])->endOfDay(),
            ])
            ->when(! $user->can('reports.view'), fn ($q) => $q->where('sales.user_id', $user->id))
            ->when(! empty($f['user_id']), fn ($q) => $q->where('sales.user_id', (int) $f['user_id']))
            ->when(! empty($f['product_id']), fn ($q) => $q->whereHas('items',
                fn ($i) => $i->where('product_id', (int) $f['product_id'])))
            ->when(! empty($f['method']), fn ($q) => $q->whereHas('payments',
                fn ($p) => $p->where('method', $f['method'])))
            ->when(! empty($f['status']), fn ($q) => $q->where('sales.status', $f['status']));
    }

    public function totals(array $f, User $user): array
    {
        $done = array_merge($f, ['status' => 'completed']);
        $ids  = fn () => $this->base($done, $user)->select('sales.id');

        $sales = $this->base($done, $user)
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(sales.total),0) as total, COALESCE(SUM(sales.discount),0) as discount')
            ->first();

        $items = SaleItem::whereIn('sale_id', $ids())
            ->when(! empty($f['product_id']), fn ($q) => $q->where('product_id', (int) $f['product_id']))
            ->selectRaw('COALESCE(SUM(qty * unit_cost),0) as cost, COALESCE(SUM(subtotal),0) as revenue')
            ->first();

        // com filtro de produto, conta só o que foi vendido desse produto
        $revenue = ! empty($f['product_id']) ? (float) $items->revenue : (float) $sales->total;
        $cost    = (float) $items->cost;
        $n       = (int) $sales->n;

        return [
            'count'     => $n,
            'revenue'   => round($revenue, 2),
            'cost'      => round($cost, 2),
            'profit'    => round($revenue - $cost, 2),
            'avg'       => $n ? round((float) $sales->total / $n, 2) : 0,
            'discount'  => (float) $sales->discount,
            'cancelled' => $this->base(array_merge($f, ['status' => 'cancelled']), $user)->count(),
        ];
    }

    public function byPayment(array $f, User $user)
    {
        $ids = $this->base(array_merge($f, ['status' => 'completed']), $user)->select('sales.id');

        return Payment::whereIn('sale_id', $ids)->groupBy('method')
            ->selectRaw('method, SUM(amount) as amount')->pluck('amount', 'method');
    }

    public function byEmployee(array $f, User $user)
    {
        $rows = $this->base(array_merge($f, ['status' => 'completed']), $user)
            ->groupBy('sales.user_id')
            ->selectRaw('sales.user_id, COUNT(*) as n, SUM(sales.total) as total')
            ->orderByDesc('total')->get();

        $names = User::whereIn('id', $rows->pluck('user_id'))->pluck('name', 'id');

        return $rows->map(fn ($r) => (object) [
            'name' => $names[$r->user_id] ?? '-', 'n' => $r->n, 'total' => (float) $r->total,
        ]);
    }

    public function topProducts(array $f, User $user, int $limit = 8)
    {
        $ids = $this->base(array_merge($f, ['status' => 'completed']), $user)->select('sales.id');

        return SaleItem::whereIn('sale_id', $ids)
            ->when(! empty($f['product_id']), fn ($q) => $q->where('product_id', (int) $f['product_id']))
            ->groupBy('product_id', 'product_name')
            ->selectRaw('product_id, product_name, SUM(qty) as qty, SUM(subtotal) as revenue, SUM(qty * unit_cost) as cost')
            ->orderByDesc('revenue')->limit($limit)->get();
    }

    public function byDay(array $f, User $user)
    {
        return $this->base(array_merge($f, ['status' => 'completed']), $user)
            ->groupBy('d')
            ->selectRaw('DATE(sales.created_at) as d, COUNT(*) as n, SUM(sales.total) as total')
            ->orderBy('d')->get();
    }
}
