<?php

namespace App\Http\Controllers;

use App\Enums\MovementType;
use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
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
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('products.index', [
            'stores'     => $this->switcherStores(),
            'storeId'    => $storeId,
            'products'   => $products,
            'categories' => Category::where('store_id', $storeId)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        return $this->form(new Product(['store_id' => $storeId, 'is_active' => true, 'unit' => 'un']), $storeId);
    }

    public function store(Request $request, StockService $stock)
    {
        $storeId = $this->currentStoreId($request);
        $data = $this->validated($request, $storeId);

        DB::transaction(function () use ($data, $storeId, $stock) {
            $product = Product::create([
                'store_id'   => $storeId,
                'category_id'=> $data['category_id'] ?? null,
                'name'       => $data['name'],
                'sku'        => $data['sku'],
                'barcode'    => $data['barcode'] ?? null,
                'unit'       => $data['unit'],
                'cost_price' => $data['cost_price'],
                'sale_price' => $data['sale_price'],
                'is_active'  => $data['is_active'],
            ]);

            ProductStock::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $storeId, 'product_id' => $product->id],
                ['min_stock' => $data['min_stock'] ?? 0]
            );

            if (($data['initial_qty'] ?? 0) > 0) {
                $stock->in($storeId, $product->id, MovementType::Adjustment,
                    (float) $data['initial_qty'], $product, (float) $data['cost_price'], 'Estoque inicial');
            }
        });

        return redirect()->route('products.index', ['store_id' => $storeId])->with('ok', 'Produto criado.');
    }

    public function edit(Product $product)
    {
        return $this->form($product->load('stock'), $product->store_id);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product->store_id, $product);

        DB::transaction(function () use ($data, $product) {
            $product->update([
                'category_id'=> $data['category_id'] ?? null,
                'name'       => $data['name'],
                'sku'        => $data['sku'],
                'barcode'    => $data['barcode'] ?? null,
                'unit'       => $data['unit'],
                'cost_price' => $data['cost_price'],
                'sale_price' => $data['sale_price'],
                'is_active'  => $data['is_active'],
            ]);

            ProductStock::withoutGlobalScopes()->updateOrCreate(
                ['store_id' => $product->store_id, 'product_id' => $product->id],
                ['min_stock' => $data['min_stock'] ?? 0]
            );
        });

        return redirect()->route('products.index', ['store_id' => $product->store_id])->with('ok', 'Produto actualizado.');
    }

    public function toggle(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('ok', $product->is_active ? 'Produto activado.' : 'Produto desactivado.');
    }

    // ---------- auxiliares ----------

    private function form(Product $product, int $storeId)
    {
        return view('products.form', [
            'product'    => $product,
            'storeId'    => $storeId,
            'categories' => Category::where('store_id', $storeId)->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request, int $storeId, ?Product $product = null): array
    {
        $request->merge([
            'name'    => trim((string) $request->input('name')),
            'sku'     => trim((string) $request->input('sku')),
            'barcode' => trim((string) $request->input('barcode')) ?: null,
        ]);

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'sku'         => ['required', 'string', 'max:60',
                Rule::unique('products', 'sku')->where('store_id', $storeId)->ignore($product?->id)],
            'barcode'     => ['nullable', 'string', 'max:60',
                Rule::unique('products', 'barcode')->where('store_id', $storeId)->ignore($product?->id)],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('store_id', $storeId)],
            'unit'        => ['required', 'string', 'max:10'],
            'cost_price'  => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'sale_price'  => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'min_stock'   => ['nullable', 'numeric', 'min:0'],
            'initial_qty' => ['nullable', 'numeric', 'min:0'],
        ], [
            'sku.unique'     => 'Já existe um produto com este SKU nesta loja.',
            'barcode.unique' => 'Já existe um produto com este código de barras nesta loja.',
        ]);

        $data['is_active']     = $request->boolean('is_active');
$data['allow_decimal'] = $request->boolean('allow_decimal');

if (! $data['allow_decimal']) {
    $labels = ['initial_qty' => 'A quantidade inicial', 'min_stock' => 'O estoque mínimo'];
    foreach ($labels as $field => $label) {
        if (isset($data[$field]) && floor((float) $data[$field]) != (float) $data[$field]) {
            throw ValidationException::withMessages([
                $field => "{$label} tem de ser um número inteiro (este produto não é vendido a peso).",
            ]);
        }
    }

    // não deixa passar a inteiro um produto que tem estoque com decimais
    if ($product && fmod((float) ($product->stock->qty ?? 0), 1) != 0) {
        throw ValidationException::withMessages([
            'allow_decimal' => 'Este produto tem estoque com decimais. Ajusta o estoque para um número inteiro antes de desmarcar esta opção.',
        ]);
    }
}

return $data;
    }
}
