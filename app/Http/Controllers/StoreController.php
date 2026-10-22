<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreController extends Controller
{
    public function index()
    {
        return view('stores.index', [
            'stores' => Store::withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('stores.form', ['store' => new Store(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        Store::create($this->data($request));
        return redirect()->route('stores.index')->with('ok', 'Loja criada.');
    }

    public function edit(Store $store)
    {
        return view('stores.form', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $store->update($this->data($request, $store));
        return redirect()->route('stores.index')->with('ok', 'Loja actualizada.');
    }

    private function data(Request $request, ?Store $store = null): array
    {
        // normaliza: tira espaços e transforma vazio em null
        $request->merge([
            'name'    => trim((string) $request->input('name')),
            'address' => trim((string) $request->input('address')) ?: null,
            'phone'   => trim((string) $request->input('phone')) ?: null,
        ]);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:40'],
        ]);

        // só é duplicada se os TRÊS campos forem iguais
        $duplicate = Store::query()
            ->where('name', $data['name'])
            ->where('address', $data['address'])
            ->where('phone', $data['phone'])
            ->when($store, fn ($q) => $q->where('id', '!=', $store->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => 'Já existe uma loja com este nome, endereço e telefone.',
            ]);
        }

        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
