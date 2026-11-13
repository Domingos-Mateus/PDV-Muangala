<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Store;
use Illuminate\Http\Request;

trait ResolvesStore
{
    /** Gerente: a sua loja. Super admin: a loja escolhida (ou a primeira). */
    protected function currentStoreId(Request $request): int
    {
        $user = auth()->user();

        $id = $user->is_super_admin
            ? ($request->integer('store_id') ?: Store::orderBy('name')->value('id'))
            : $user->store_id;

        abort_unless($id && Store::whereKey($id)->exists(), 404, 'Cria primeiro uma loja.');

        return (int) $id;
    }

    /** Lojas para o seletor (só o super admin vê). */
    protected function switcherStores()
    {
        return auth()->user()->is_super_admin ? Store::orderBy('name')->get() : collect();
    }
}
