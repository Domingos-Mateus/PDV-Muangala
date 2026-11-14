@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Caixas</h1>

@include('partials.store-switcher')

<form method="POST" action="{{ route('cash.registers.store') }}" class="flex gap-2 mb-4">
    @csrf
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <input name="name" placeholder="Ex.: Caixa 1" class="border rounded p-2 w-64">
    <button class="bg-blue-600 text-white rounded px-4">Adicionar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm max-w-2xl">
    <thead class="text-left border-b"><tr><th class="p-3">Nome</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($registers as $r)
        <tr class="border-b {{ $r->is_active ? '' : 'opacity-50' }}">
            <td class="p-3">
                <form method="POST" action="{{ route('cash.registers.update', $r) }}" class="flex gap-2">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $r->name }}" class="border rounded p-1">
                    <button class="text-blue-600">Guardar</button>
                </form>
            </td>
            <td>{{ $r->is_active ? 'Activo' : 'Inactivo' }}</td>
            <td class="text-right pr-3">
                <form method="POST" action="{{ route('cash.registers.toggle', $r) }}" class="inline"
                      data-confirm="{{ $r->is_active ? 'Desactivar' : 'Activar' }} {{ $r->name }}?">
                    @csrf @method('PATCH')
                    <button class="text-gray-600">{{ $r->is_active ? 'Desactivar' : 'Activar' }}</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="3" class="p-4 text-gray-500">Ainda não há caixas. Cria o primeiro.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
