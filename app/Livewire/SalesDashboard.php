<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Store;
use App\Models\User;
use App\Services\SalesReport;
use Livewire\Component;

class SalesDashboard extends Component
{
    public $storeId = null;
    public string $from = '';
    public string $to = '';
    public $userId = '';
    public $productId = '';

    public function mount(): void
    {
        $u = auth()->user();
        abort_unless($u->is_super_admin || $u->store_id, 403);

        $this->storeId = $u->is_super_admin ? Store::orderBy('name')->value('id') : $u->store_id;
        $this->from = $this->to = today()->toDateString();
    }

    public function updatedStoreId(): void
    {
        $this->userId = '';
        $this->productId = '';
    }

    public function period(string $p): void
    {
        if ($p === 'today') {
            $this->from = $this->to = today()->toDateString();
        } elseif ($p === 'yesterday') {
            $this->from = $this->to = today()->subDay()->toDateString();
        } elseif ($p === 'month') {
            $this->from = today()->startOfMonth()->toDateString();
            $this->to   = today()->toDateString();
        } elseif ($p === 'lastmonth') {
            $this->from = today()->subMonthNoOverflow()->startOfMonth()->toDateString();
            $this->to   = today()->subMonthNoOverflow()->endOfMonth()->toDateString();
        }
    }

    private function filters(): array
    {
        $u = auth()->user();

        return [
            // o servidor decide a loja: um gerente nunca vê outra, mesmo que altere o pedido
            'store_id'   => $u->is_super_admin ? (int) $this->storeId : (int) $u->store_id,
            'from'       => $this->from,
            'to'         => $this->to,
            'user_id'    => $this->userId,
            'product_id' => $this->productId,
        ];
    }

    public function render(SalesReport $report)
    {
        $u = auth()->user();
        $f = $this->filters();

        if (! $f['store_id']) {
            return view('livewire.sales-dashboard', ['empty' => true]);
        }

        $isBoss = $u->can('reports.view');

        return view('livewire.sales-dashboard', [
            'empty'     => false,
            'isBoss'    => $isBoss,
            'stores'    => $u->is_super_admin ? Store::orderBy('name')->get() : collect(),
            'users'     => $isBoss ? User::where('store_id', $f['store_id'])->orderBy('name')->get(['id', 'name']) : collect(),
            'products'  => Product::withoutGlobalScopes()->where('store_id', $f['store_id'])->orderBy('name')->get(['id', 'name']),
            'totals'    => $report->totals($f, $u),
            'payments'  => $report->byPayment($f, $u),
            'employees' => $isBoss ? $report->byEmployee($f, $u) : collect(),
            'top'       => $report->topProducts($f, $u),
            'days'      => $report->byDay($f, $u),
            'recent'    => $report->base($f, $u)->with('user:id,name')->latest('sales.id')->limit(10)->get(),
            'lowStock'  => ProductStock::withoutGlobalScopes()
                ->where('product_stocks.store_id', $f['store_id'])
                ->join('products', 'products.id', '=', 'product_stocks.product_id')
                ->where('products.is_active', true)
                ->whereColumn('product_stocks.qty', '<=', 'product_stocks.min_stock')->count(),
            'f' => $f,
        ]);
    }
}
