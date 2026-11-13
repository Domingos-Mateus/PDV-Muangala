@extends('layouts.app')

@php
    $statusLabels = ['pending' => 'Pendente', 'received' => 'Recebida', 'cancelled' => 'Cancelada'];
@endphp

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Compra #{{ $purchase->id }}</h1>
        <p class="text-gray-500">
            {{ $purchase->supplier?->name }} · {{ $purchase->created_at->format('d/m/Y H:i') }}
            · {{ $purchase->user?->name }} · <strong>{{ $statusLabels[$purchase->status] ?? $purchase->status }}</strong>
            @if ($purchase->received_at) · recebida em {{ $purchase->received_at->format('d/m/Y H:i') }} @endif
        </p>
    </div>
    <a href="{{ route('purchases.index', ['store_id' => $purchase->store_id]) }}" class="text-blue-600">Voltar</a>
</div>

<table class="w-full bg-white rounded shadow text-sm mb-4">
    <thead class="text-left border-b">
        <tr><th class="p-3">Produto</th><th>Quantidade</th><th>Custo unit.</th><th class="text-right pr-3">Subtotal</th></tr>
    </thead>
    <tbody>
    @foreach ($purchase->items as $i)
        <tr class="border-b">
            <td class="p-3">{{ $i->product?->name }}</td>
            <td>{{ (float) $i->qty + 0 }} {{ $i->product?->unit }}</td>
            <td>{{ number_format($i->unit_cost, 2, ',', '.') }}</td>
            <td class="text-right pr-3">{{ number_format($i->subtotal, 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="3" class="p-3 text-right font-bold">Total</td>
            <td class="text-right pr-3 font-bold">{{ number_format($purchase->total, 2, ',', '.') }}</td></tr>
    </tfoot>
</table>

@if ($purchase->notes)
    <p class="text-sm text-gray-600 mb-4">Notas: {{ $purchase->notes }}</p>
@endif

@if ($purchase->status === 'pending')
    <div class="flex gap-3">
        @can('purchases.receive')
            <form method="POST" action="{{ route('purchases.receive', $purchase) }}"
                  data-confirm="Receber esta compra e dar entrada no estoque?">
                @csrf
                <button class="bg-green-600 text-white rounded px-4 py-2">Receber compra</button>
            </form>
        @endcan
        @can('purchases.create')
            <form method="POST" action="{{ route('purchases.cancel', $purchase) }}"
                  data-confirm="Cancelar esta compra?">
                @csrf
                <button class="border border-red-300 text-red-600 rounded px-4 py-2">Cancelar compra</button>
            </form>
        @endcan
    </div>
@endif
@endsection
