@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Serviços</h1>

@include('partials.store-switcher')

<form method="POST" action="{{ route('services.store') }}" class="flex flex-wrap gap-2 mb-4">
    @csrf
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <input name="name" placeholder="Ex.: Recolha de lixo" class="border rounded p-2 w-64">
    <input name="default_price" type="number" step="0.01" min="0" placeholder="Preço base" class="border rounded p-2 w-40">
    <button class="bg-blue-600 text-white rounded px-4">Adicionar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm max-w-3xl">
    <thead class="text-left border-b"><tr><th class="p-3">Serviço</th><th>Preço base</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($services as $s)
        <tr class="border-b {{ $s->is_active ? '' : 'opacity-50' }}">
            <td class="p-3" colspan="2">
                <form method="POST" action="{{ route('services.update', $s) }}" class="flex gap-2">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $s->name }}" class="border rounded p-1 w-56">
                    <input name="default_price" type="number" step="0.01" min="0" value="{{ $s->default_price }}" class="border rounded p-1 w-32">
                    <button class="text-blue-600">Guardar</button>
                </form>
            </td>
            <td>{{ $s->is_active ? 'Activo' : 'Inactivo' }}</td>
            <td class="text-right pr-3">
                <form method="POST" action="{{ route('services.toggle', $s) }}" class="inline" data-confirm="{{ $s->is_active ? 'Desactivar' : 'Activar' }} {{ $s->name }}?">
                    @csrf @method('PATCH')
                    <button class="text-gray-600">{{ $s->is_active ? 'Desactivar' : 'Activar' }}</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="p-4 text-gray-500">Ainda não há serviços. Cria "Recolha de lixo" e "Água".</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
