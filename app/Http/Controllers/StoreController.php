<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StoreController extends Controller
{
    public function index()
    {
        ActivityLog::trace('StoreController@index', 'Listou lojas');

        return view('stores.index', [
            'stores' => Store::withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        ActivityLog::trace('StoreController@create', 'Abriu o formulário de nova loja');

        return view('stores.form', ['store' => new Store(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        ActivityLog::trace('StoreController@store', 'A criar loja');

        try {
            $store = Store::create($this->data($request));

            ActivityLog::record($store, 'created', "Loja «{$store->name}»", [
                'depois' => $store->only(['name', 'address', 'phone', 'is_active']),
            ]);
        } catch (ValidationException $e) {
            ActivityLog::trace('StoreController@store', 'Validação falhou', ['erros' => $e->errors()], 'warning');
            throw $e;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            ActivityLog::trace('StoreController@store', 'Erro ao criar loja', ['erro' => $e->getMessage()], 'error');
            throw $e;
        }

        return redirect()->route('stores.index')->with('ok', 'Loja criada.');
    }

    public function edit(Store $store)
    {
        ActivityLog::trace('StoreController@edit', "Abriu a edição da loja «{$store->name}»", ['store_id' => $store->id]);

        return view('stores.form', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        ActivityLog::trace('StoreController@update', "A actualizar loja «{$store->name}»", ['store_id' => $store->id]);

        try {
            $store->fill($this->data($request, $store));

            if ($store->isDirty()) {
                $props = ActivityLog::dirty($store);
                $store->save();
                ActivityLog::record($store, 'updated', "Loja «{$store->name}»", $props);
            } else {
                ActivityLog::trace('StoreController@update', 'Sem alterações', ['store_id' => $store->id]);
            }
        } catch (ValidationException $e) {
            ActivityLog::trace('StoreController@update', 'Validação falhou', ['erros' => $e->errors()], 'warning');
            throw $e;
        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            ActivityLog::trace('StoreController@update', 'Erro ao actualizar loja', ['erro' => $e->getMessage()], 'error');
            throw $e;
        }

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
