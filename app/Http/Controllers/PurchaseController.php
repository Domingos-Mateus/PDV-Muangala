<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    use ResolvesStore;

    public function index(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        $purchases = Purchase::where('store_id', $storeId)
            ->with(['supplier', 'user:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('purchases.index', [
            'stores'    => $this->switcherStores(),
            'storeId'   => $storeId,
            'purchases' => $purchases,
            'suppliers' => Supplier::where('store_id', $storeId)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        return view('purchases.create', [
            'storeId'   => $storeId,
            'suppliers' => Supplier::where('store_id', $storeId)->where('is_active', true)->orderBy('name')->get(),
            'products'  => Product::where('store_id', $storeId)->where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'sku', 'unit', 'cost_price']),
        ]);
    }

    public function store(Request $request, PurchaseService $service)
    {
        $storeId = $this->currentStoreId($request);

        $data = $request->validate([
            'supplier_id'        => ['required', Rule::exists('suppliers', 'id')->where('store_id', $storeId)],
            'notes'              => ['nullable', 'string', 'max:500'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where('store_id', $storeId)],
            'items.*.qty'        => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'items.*.unit_cost'  => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ], [
            'items.required'          => 'Adiciona pelo menos um produto.',
            'items.*.product_id.distinct' => 'O mesmo produto aparece duas vezes na compra.',
        ]);

        try {
            $purchase = $service->create([
                'store_id'    => $storeId,
                'supplier_id' => $data['supplier_id'],
                'user_id'     => auth()->id(),
                'notes'       => $data['notes'] ?? null,
            ], $data['items']);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors($e->getMessage());
        }

        return redirect()->route('purchases.show', $purchase)->with('ok', 'Compra registada. Falta receber para dar entrada no estoque.');
    }

    public function show(Purchase $purchase)
    {
        return view('purchases.show', [
            'purchase' => $purchase->load(['supplier', 'user:id,name', 'items.product']),
        ]);
    }

    public function receive(Purchase $purchase, PurchaseService $service)
    {
        try {
            $service->receive($purchase);
        } catch (DomainException $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('ok', 'Compra recebida e estoque actualizado.');
    }

    public function cancel(Purchase $purchase, PurchaseService $service)
    {
        try {
            $service->cancel($purchase);
        } catch (DomainException $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->with('ok', 'Compra cancelada.');
    }
}
