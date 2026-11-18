@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Clientes</h1>
    <a href="{{ route('customers.create', ['store_id' => $storeId]) }}" class="bg-blue-600 text-white rounded px-4 py-2">Novo cliente</a>
</div>

@include('partials.store-switcher')

<form method="GET" class="flex gap-2 mb-4">
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <input name="q" value="{{ request('q') }}" placeholder="Nome, bloco, apartamento ou telefone" class="border rounded p-2 w-80">
    <button class="border rounded px-4 bg-white">Pesquisar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b"><tr><th class="p-3">Nome</th><th>Bloco / Apt.</th><th>Telefone</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($customers as $c)
        <tr class="border-b {{ $c->is_active ? '' : 'opacity-50' }}">
            <td class="p-3">{{ $c->name }}</td>
            <td>{{ trim($c->block.' '.$c->unit) }}</td>
            <td>{{ $c->phone }}</td>
            <td>{{ $c->is_active ? 'Activo' : 'Inactivo' }}</td>
            <td class="text-right pr-3 space-x-3">
                <a class="text-blue-600" href="{{ route('customers.edit', $c) }}">Editar</a>
                <form method="POST" action="{{ route('customers.toggle', $c) }}" class="inline"
                      data-confirm="{{ $c->is_active ? 'Desactivar' : 'Activar' }} {{ $c->name }}?">
                    @csrf @method('PATCH')
                    <button class="text-gray-600">{{ $c->is_active ? 'Desactivar' : 'Activar' }}</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="p-4 text-gray-500">Nenhum cliente encontrado.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $customers->links() }}</div>
@endsection
