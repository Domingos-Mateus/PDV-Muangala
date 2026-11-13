<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStore;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    use ResolvesStore;

    public function index(Request $request)
    {
        $storeId = $this->currentStoreId($request);

        return view('suppliers.index', [
            'stores'    => $this->switcherStores(),
            'storeId'   => $storeId,
            'suppliers' => Supplier::where('store_id', $storeId)->withCount('purchases')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        return view('suppliers.form', [
            'supplier' => new Supplier(['store_id' => $storeId, 'is_active' => true]),
            'storeId'  => $storeId,
        ]);
    }

    public function store(Request $request)
    {
        $storeId = $this->currentStoreId($request);
        Supplier::create(['store_id' => $storeId] + $this->validated($request, $storeId));

        return redirect()->route('suppliers.index', ['store_id' => $storeId])->with('ok', 'Fornecedor criado.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', ['supplier' => $supplier, 'storeId' => $supplier->store_id]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request, $supplier->store_id, $supplier));

        return redirect()->route('suppliers.index', ['store_id' => $supplier->store_id])->with('ok', 'Fornecedor actualizado.');
    }

    public function toggle(Supplier $supplier)
    {
        $supplier->update(['is_active' => ! $supplier->is_active]);
        return back()->with('ok', $supplier->is_active ? 'Fornecedor activado.' : 'Fornecedor desactivado.');
    }

    private function validated(Request $request, int $storeId, ?Supplier $supplier = null): array
    {
        $request->merge([
            'name'    => trim((string) $request->input('name')),
            'nif'     => trim((string) $request->input('nif')) ?: null,
            'phone'   => trim((string) $request->input('phone')) ?: null,
            'email'   => trim((string) $request->input('email')) ?: null,
            'address' => trim((string) $request->input('address')) ?: null,
        ]);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:150'],
            'nif'     => ['nullable', 'string', 'max:30',
                Rule::unique('suppliers', 'nif')->where('store_id', $storeId)->ignore($supplier?->id)],
            'phone'   => ['nullable', 'string', 'max:40'],
            'email'   => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
        ], ['nif.unique' => 'Já existe um fornecedor com este NIF nesta loja.']);

        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
