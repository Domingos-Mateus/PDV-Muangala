@extends('layouts.app')

@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $labels = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'transfer' => 'Transferência'];
@endphp

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Recibo {{ $r->number }}
            @if ($r->status === 'cancelled') <span class="text-red-600 text-base">(cancelado)</span> @endif
        </h1>
        <p class="text-gray-500">
            {{ $r->created_at->format('d/m/Y H:i') }} · recebido por {{ $r->user?->name }} · {{ $r->session?->register?->name }}
        </p>
        <p class="text-gray-500">Cliente: <strong>{{ $r->customer?->name }}</strong> {{ trim($r->customer?->block.' '.$r->customer?->unit) }} {{ $r->customer?->phone }}</p>
    </div>
    <div class="space-x-4">
        <a href="{{ route('billing.receipt', $r) }}" target="_blank" class="text-blue-600">Imprimir recibo</a>
        <a href="{{ route('billing.report', ['store_id' => $r->store_id]) }}" class="text-gray-600">Voltar</a>
    </div>
</div>

<table class="w-full bg-white rounded shadow text-sm mb-4">
    <thead class="text-left border-b"><tr><th class="p-3">Período</th><th>Estado actual</th><th class="text-right pr-3">Pago neste recibo</th></tr></thead>
    <tbody>
    @foreach ($r->allocations as $a)
        <tr class="border-b">
            <td class="p-3">{{ $a->charge?->description }}</td>
            <td>
                @if ($a->charge?->status === 'paid') Pago
                @else Em dívida: {{ $fmt($a->charge?->balance()) }}
                @endif
            </td>
            <td class="text-right pr-3">{{ $fmt($a->amount) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr><td colspan="2" class="p-3 text-right font-bold">Total</td><td class="text-right pr-3 font-bold">{{ $fmt($r->total) }}</td></tr>
    </tfoot>
</table>

<div class="bg-white rounded shadow p-4 text-sm max-w-md mb-4">
    <h2 class="font-semibold mb-2">Pagamento</h2>
    @foreach ($r->payments as $p)
        <div class="flex justify-between py-1 border-b">
            <span>{{ $labels[$p->method] ?? $p->method }}</span>
            <span>
                {{ $fmt($p->amount) }}
                @if ($p->method === 'cash') <small class="text-gray-500">(entregue {{ $fmt($p->tendered) }}, troco {{ $fmt($p->change_given) }})</small> @endif
            </span>
        </div>
    @endforeach
</div>

@if ($r->status === 'cancelled')
    <div class="bg-red-50 text-red-800 rounded p-4 text-sm max-w-md">
        Cancelado em {{ $r->cancelled_at?->format('d/m/Y H:i') }} por {{ $r->canceller?->name }}. Motivo: {{ $r->cancel_reason }}
    </div>
@else
    @can('billing.cancel')
        <form method="POST" action="{{ route('billing.cancel', $r) }}" class="bg-white rounded shadow p-4 max-w-md space-y-3"
              data-confirm="Cancelar este recibo? Os períodos voltam a ficar por pagar.">
            @csrf
            <h2 class="font-semibold">Cancelar recibo</h2>
            <input name="reason" maxlength="200" placeholder="Motivo (obrigatório)" class="w-full border rounded p-2">
            <button class="border border-red-300 text-red-600 rounded px-4 py-2">Cancelar recibo</button>
            <p class="text-xs text-gray-500">Só é possível enquanto o turno de caixa deste recibo estiver aberto. Depois do fecho, o valor esperado já ficou gravado.</p>
        </form>
    @endcan
@endif
@endsection
