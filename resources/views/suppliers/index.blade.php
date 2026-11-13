@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Fornecedores</h1>
    <a href="{{ route('suppliers.create', ['store_id' => $storeId]) }}" class="bg-blue-600 text-white rounded px-4 py-2">Novo fornecedor</a>
</div>

@include('partials.store-switcher')

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Nome</th><th>NIF</th><th>Telefone</th><th>Compras</th><th>Estado</th><th></th></tr>
    </thead>
    <tbody>
    @forelse ($suppliers as $s)
        <tr class="border-b {{ $s->is_active ? '' : 'opacity-50' }}">
            <td class="p-3">{{ $s->name }}</td>
            <td>{{ $s->nif }}</td>
            <td>{{ $s->phone }}</td>
            <td>{{ $s->purchases_count }}</td>
            <td>{{ $s->is_active ? 'Activo' : 'Inactivo' }}</td>
            <td class="text-right pr-3 space-x-3">
                <a class="text-blue-600" href="{{ route('suppliers.edit', $s) }}">Editar</a>
                <form method="POST" action="{{ route('suppliers.toggle', $s) }}" class="inline"
                      data-confirm="{{ $s->is_active ? 'Desactivar' : 'Activar' }} {{ $s->name }}?">
                    @csrf @method('PATCH')
                    <button class="text-gray-600">{{ $s->is_active ? 'Desactivar' : 'Activar' }}</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-gray-500">Ainda não há fornecedores.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
