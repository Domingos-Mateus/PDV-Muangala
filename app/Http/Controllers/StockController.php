<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ResolvesStore;

    public function index(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        $q = trim((string) $request->query('q'));

        $products = Product::where('store_id', $storeId)
            ->with(['category', 'stock'])
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")
                ->orWhere('barcode', 'like', "%{$q}%")))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->boolean('low'), fn ($query) => $query->whereHas('stock',
                fn ($s) => $s->whereColumn('qty', '<=', 'min_stock')))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $totals = Product::where('products.store_id', $storeId)
            ->join('product_stocks', 'product_stocks.product_id', '=', 'products.id')
            ->selectRaw('COALESCE(SUM(product_stocks.qty * products.cost_price),0) as cost,
                         COALESCE(SUM(product_stocks.qty * products.sale_price),0) as sale')
            ->first();

        $lowCount = Product::where('store_id', $storeId)->where('is_active', true)
            ->whereHas('stock', fn ($s) => $s->whereColumn('qty', '<=', 'min_stock'))
            ->count();

        return view('stock.index', [
            'stores'     => $this->switcherStores(),
            'storeId'    => $storeId,
            'products'   => $products,
            'categories' => Category::where('store_id', $storeId)->orderBy('name')->get(),
            'totals'     => $totals,
            'lowCount'   => $lowCount,
        ]);
    }

    public function adjustForm(Product $product)
    {
        return view('stock.adjust', ['product' => $product->load('stock')]);
    }

    public function adjust(Request $request, Product $product, StockService $stock)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:in,out,count'],
            'qty'  => ['required', 'numeric', 'min:0', 'max:99999999'],
            'note' => ['required', 'string', 'max:200'],
        ], ['note.required' => 'Indica o motivo do ajuste.']);

        $qty  = (float) $data['qty'];
        $note = trim($data['note']);
        if (! $product->allow_decimal && floor($qty) != $qty) {
    return back()->withInput()->withErrors('Este produto só aceita quantidades inteiras.');
}

        if ($data['mode'] !== 'count' && $qty <= 0) {
            return back()->withInput()->withErrors('A quantidade tem de ser maior que zero.');
        }

        try {
            match ($data['mode']) {
                'in'    => $stock->in($product->store_id, $product->id, MovementType::Adjustment, $qty, null, null, $note),
                'out'   => $stock->out($product->store_id, $product->id, MovementType::Adjustment, $qty, null, null, $note),
                'count' => $stock->setTo($product->store_id, $product->id, $qty, $note),
            };
        } catch (InsufficientStockException $e) {
            return back()->withInput()->withErrors($e->getMessage());
        }

        return redirect()->route('stock.index', ['store_id' => $product->store_id])
            ->with('ok', 'Estoque actualizado.');
    }

    public function history(Product $product)
    {
        $movements = StockMovement::withoutGlobalScopes()
            ->where('store_id', $product->store_id)
            ->where('product_id', $product->id)
            ->with('user:id,name')
            ->latest('id')
            ->paginate(30);

        return view('stock.history', compact('product', 'movements'));
    }
}
