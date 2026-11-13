@extends('layouts.app')

@php
    $statusLabels = ['pending' => 'Pendente', 'received' => 'Recebida', 'cancelled' => 'Cancelada'];
    $statusColors = ['pending' => 'text-yellow-700', 'received' => 'text-green-700', 'cancelled' => 'text-gray-500'];
@endphp

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Compras</h1>
    @can('purchases.create')
        <a href="{{ route('purchases.create', ['store_id' => $storeId]) }}" class="bg-blue-600 text-white rounded px-4 py-2">Nova compra</a>
    @endcan
</div>

@include('partials.store-switcher')

<form method="GET" class="flex gap-2 mb-4">
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <select name="status" class="border rounded p-2 bg-white">
        <option value="">Todos os estados</option>
        @foreach ($statusLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
        @endforeach
    </select>
    <select name="supplier_id" class="border rounded p-2 bg-white">
        <option value="">Todos os fornecedores</option>
        @foreach ($suppliers as $s)
            <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
    <button class="border rounded px-4 bg-white">Filtrar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Nº</th><th>Data</th><th>Fornecedor</th><th>Total</th><th>Estado</th><th>Registada por</th><th></th></tr>
    </thead>
    <tbody>
    @forelse ($purchases as $p)
        <tr class="border-b">
            <td class="p-3">#{{ $p->id }}</td>
            <td>{{ $p->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $p->supplier?->name }}</td>
            <td>{{ number_format($p->total, 2, ',', '.') }}</td>
            <td class="{{ $statusColors[$p->status] ?? '' }}">{{ $statusLabels[$p->status] ?? $p->status }}</td>
            <td>{{ $p->user?->name }}</td>
            <td class="text-right pr-3"><a class="text-blue-600" href="{{ route('purchases.show', $p) }}">Ver</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="p-4 text-gray-500">Nenhuma compra encontrada.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $purchases->links() }}</div>
@endsection
