@extends('layouts.app')

@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $labels = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'transfer' => 'Transferência'];
    $cost = $sale->items->sum(fn ($i) => $i->qty * $i->unit_cost);
@endphp

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Venda {{ $sale->number }}
            @if ($sale->status === 'cancelled') <span class="text-red-600 text-base">(cancelada)</span> @endif
        </h1>
        <p class="text-gray-500">{{ $sale->created_at->format('d/m/Y H:i') }} · {{ $sale->user?->name }} · {{ $sale->session?->register?->name }}</p>
    </div>
    <div class="space-x-4">
        <a href="{{ route('sales.receipt', $sale) }}" target="_blank" class="text-blue-600">Recibo</a>
        <a href="{{ route('sales.index', ['store_id' => $sale->store_id]) }}" class="text-gray-600">Voltar</a>
    </div>
</div>

<table class="w-full bg-white rounded shadow text-sm mb-4">
    <thead class="text-left border-b"><tr><th class="p-3">Produto</th><th>Quantidade</th><th>Preço</th><th class="text-right pr-3">Subtotal</th></tr></thead>
    <tbody>
    @foreach ($sale->items as $i)
        <tr class="border-b">
            <td class="p-3">{{ $i->product_name }}</td>
            <td>{{ (float) $i->qty + 0 }}</td>
            <td>{{ $fmt($i->unit_price) }}</td>
            <td class="text-right pr-3">{{ $fmt($i->subtotal) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        @if ($sale->discount > 0)
            <tr><td colspan="3" class="p-2 text-right">Desconto</td><td class="text-right pr-3">-{{ $fmt($sale->discount) }}</td></tr>
        @endif
        <tr><td colspan="3" class="p-3 text-right font-bold">Total</td><td class="text-right pr-3 font-bold">{{ $fmt($sale->total) }}</td></tr>
        @can('reports.view')
            <tr><td colspan="3" class="p-2 text-right text-gray-500">Lucro</td><td class="text-right pr-3 text-gray-500">{{ $fmt($sale->total - $cost) }}</td></tr>
        @endcan
    </tfoot>
</table>

<div class="bg-white rounded shadow p-4 text-sm max-w-md mb-4">
    <h2 class="font-semibold mb-2">Pagamentos</h2>
    @foreach ($sale->payments as $p)
        <div class="flex justify-between py-1 border-b">
            <span>{{ $labels[$p->method] ?? $p->method }}</span>
            <span>
                {{ $fmt($p->amount) }}
                @if ($p->method === 'cash') <small class="text-gray-500">(entregue {{ $fmt($p->tendered) }}, troco {{ $fmt($p->change_given) }})</small> @endif
            </span>
        </div>
    @endforeach
</div>

@if ($sale->status === 'cancelled')
    <div class="bg-red-50 text-red-800 rounded p-4 text-sm max-w-md">
        Cancelada em {{ $sale->cancelled_at?->format('d/m/Y H:i') }}. Motivo: {{ $sale->cancel_reason }}
    </div>
@else
    @can('sales.cancel')
        <form method="POST" action="{{ route('sales.cancel', $sale) }}" class="bg-white rounded shadow p-4 max-w-md space-y-3"
              data-confirm="Cancelar esta venda? O estoque será devolvido.">
            @csrf
            <h2 class="font-semibold">Cancelar venda</h2>
            <input name="reason" maxlength="200" placeholder="Motivo (obrigatório)" class="w-full border rounded p-2">
            <button class="border border-red-300 text-red-600 rounded px-4 py-2">Cancelar venda</button>
            <p class="text-xs text-gray-500">Só é possível enquanto o turno desta venda estiver aberto.</p>
        </form>
    @endcan
@endif
@endsection
