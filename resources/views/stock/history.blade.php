@extends('layouts.app')

@php
    $labels = [
        'purchase' => 'Compra', 'sale' => 'Venda', 'adjustment' => 'Ajuste',
        'cancel' => 'Cancelamento', 'transfer_in' => 'Transferência (entrada)', 'transfer_out' => 'Transferência (saída)',
    ];
@endphp

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Histórico de estoque</h1>
        <p class="text-gray-500">{{ $product->name }} ({{ $product->sku }})</p>
    </div>
    <a href="{{ route('stock.index', ['store_id' => $product->store_id]) }}" class="text-blue-600">Voltar</a>
</div>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Data</th><th>Tipo</th><th>Qtd.</th><th>Antes</th><th>Depois</th><th>Utilizador</th><th>Nota</th></tr>
    </thead>
    <tbody>
    @forelse ($movements as $m)
        @php $q = (float) $m->qty; @endphp
        <tr class="border-b">
            <td class="p-3">{{ $m->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $labels[$m->type->value] ?? $m->type->value }}</td>
            <td class="font-semibold {{ $q < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $q > 0 ? '+' : '' }}{{ $q + 0 }}</td>
            <td>{{ (float) $m->qty_before + 0 }}</td>
            <td>{{ (float) $m->qty_after + 0 }}</td>
            <td>{{ $m->user?->name ?? '-' }}</td>
            <td>{{ $m->note }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="p-4 text-gray-500">Sem movimentos.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $movements->links() }}</div>
@endsection
