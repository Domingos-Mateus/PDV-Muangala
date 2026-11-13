<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use ResolvesStore;

    public function index(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        return view('categories.index', [
            'stores'     => $this->switcherStores(),
            'storeId'    => $storeId,
            'categories' => Category::where('store_id', $storeId)
                ->withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        $data = $this->validated($request, $storeId);

        Category::create(['store_id' => $storeId, 'name' => $data['name']]);

        return redirect()->route('categories.index', ['store_id' => $storeId])->with('ok', 'Categoria criada.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category->store_id, $category);
        $category->update(['name' => $data['name']]);

        return redirect()->route('categories.index', ['store_id' => $category->store_id])->with('ok', 'Categoria actualizada.');
    }

    public function destroy(Category $category)
    {
        $storeId = $category->store_id;
        $category->delete(); // os produtos ficam sem categoria (nullOnDelete)

        return redirect()->route('categories.index', ['store_id' => $storeId])->with('ok', 'Categoria apagada.');
    }

    private function validated(Request $request, int $storeId, ?Category $category = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => [
                'required', 'string', 'max:80',
                Rule::unique('categories', 'name')->where('store_id', $storeId)->ignore($category?->id),
            ],
        ], ['name.unique' => 'Esta loja já tem uma categoria com esse nome.']);
    }
}
