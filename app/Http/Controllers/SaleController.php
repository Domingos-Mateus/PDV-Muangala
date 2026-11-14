<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SalesReport;
use App\Services\SaleService;
use DomainException;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    use ResolvesStore;
    public function pdv()
    {
        $user = auth()->user();
        abort_unless($user->store_id, 403, 'Só utilizadores de uma loja vendem.');

        $open = CashSession::where('user_id', $user->id)->where('status', 'open')->exists();
        if (! $open) {
            return redirect()->route('cash.current')->withErrors('Abre o teu turno de caixa antes de vender.');
        }

        return view('pdv.index');
    }

    public function receipt(Sale $sale)
    {
        $user = auth()->user();
        abort_unless($user->can('reports.view') || $sale->user_id === $user->id, 403);

        return view('sales.receipt', [
            'sale' => $sale->load(['store', 'user:id,name', 'items', 'payments']),
        ]);
    }

    public function index(Request $request, SalesReport $report)
{
    $user    = auth()->user();
    $storeId = $this->currentStoreId($request);

    $f = $report->normalize([
        'store_id'   => $storeId,
        'from'       => $request->query('from') ?: today()->toDateString(),
        'to'         => $request->query('to') ?: today()->toDateString(),
        'user_id'    => $request->query('user_id'),
        'product_id' => $request->query('product_id'),
        'method'     => $request->query('method'),
        'status'     => $request->query('status'),
    ]);

    $sales = $report->base($f, $user)
        ->with(['user:id,name', 'payments'])
        ->latest('sales.id')->paginate(25)->withQueryString();

    return view('sales.index', [
        'stores'   => $this->switcherStores(),
        'storeId'  => $storeId,
        'f'        => $f,
        'sales'    => $sales,
        'totals'   => $report->totals($f, $user),
        'users'    => $user->can('reports.view') ? User::where('store_id', $storeId)->orderBy('name')->get(['id', 'name']) : collect(),
        'products' => Product::withoutGlobalScopes()->where('store_id', $storeId)->orderBy('name')->get(['id', 'name']),
    ]);
}

public function show(Sale $sale)
{
    $user = auth()->user();
    abort_unless($user->can('reports.view') || $sale->user_id === $user->id, 403);

    return view('sales.show', [
        'sale' => $sale->load(['store', 'user:id,name', 'items', 'payments', 'session.register']),
    ]);
}

public function cancel(Request $request, Sale $sale, SaleService $sales)
{
    $data = $request->validate(
        ['reason' => ['required', 'string', 'max:200']],
        ['reason.required' => 'Indica o motivo do cancelamento.']
    );

    try {
        $sales->cancel($sale, auth()->user(), trim($data['reason']));
    } catch (DomainException $e) {
        return back()->withErrors($e->getMessage());
    }

    return back()->with('ok', 'Venda cancelada e estoque devolvido.');
}
}
