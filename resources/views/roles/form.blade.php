@extends('layouts.app')

@php
    $labels = [
        'catalog' => 'Produtos', 'stock' => 'Estoque', 'sales' => 'Vendas',
        'purchases' => 'Compras e fornecedores', 'users' => 'Utilizadores e perfis',
    ];
@endphp

@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $role->exists ? 'Editar perfil' : 'Novo perfil' }}</h1>
<p class="text-gray-500 mb-4">Loja: {{ $store?->name }}</p>

<form method="POST"
      action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}"
      class="bg-white p-6 rounded shadow max-w-2xl space-y-5">
    @csrf
    @if ($role->exists) @method('PUT') @endif
    <input type="hidden" name="store_id" value="{{ $storeId }}">

    <input name="name" value="{{ old('name', $role->name) }}" placeholder="Nome do perfil"
           class="w-full border rounded p-2">

    @foreach ($groups as $group => $perms)
        <fieldset class="border rounded p-3">
            <legend class="px-2 font-semibold flex items-center gap-3">
                {{ $labels[$group] ?? $group }}
                <button type="button" class="text-xs text-blue-600 font-normal"
                        onclick="this.closest('fieldset').querySelectorAll('input[type=checkbox]:not(:disabled)').forEach(c => c.checked = !c.checked)">
                    marcar/desmarcar
                </button>
            </legend>
            <div class="grid sm:grid-cols-2 gap-2">
                @foreach ($perms as $p)
                    @php $can = in_array($p->id, $allowed); @endphp
                    <label class="flex items-center gap-2 text-sm {{ $can ? '' : 'opacity-50' }}">
                        <input type="checkbox" name="permissions[]" value="{{ $p->id }}"
                               @checked(in_array($p->id, old('permissions', $selected)))
                               @disabled(! $can)>
                        {{ $p->label }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endforeach

    <div class="flex gap-3">
        <button class="bg-blue-600 text-white rounded px-4 py-2">Guardar</button>
        <a href="{{ route('roles.index', ['store_id' => $storeId]) }}" class="px-4 py-2 text-gray-600">Cancelar</a>
    </div>
</form>
@endsection
