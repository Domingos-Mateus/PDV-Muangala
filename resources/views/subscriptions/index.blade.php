@extends('layouts.app')

@php
    $freq = ['daily' => 'Diária', 'weekly' => 'Semanal', 'monthly' => 'Mensal'];
    $st = ['active' => 'Activo', 'paused' => 'Pausado', 'ended' => 'Terminado'];
@endphp

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Contratos</h1>
    <a href="{{ route('subscriptions.create', ['store_id' => $storeId]) }}" class="bg-blue-600 text-white rounded px-4 py-2">Novo contrato</a>
</div>

@include('partials.store-switcher')

<form method="GET" class="flex gap-2 mb-4">
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <input name="q" value="{{ request('q') }}" placeholder="Cliente, bloco ou apartamento" class="border rounded p-2 w-72">
    <select name="status" class="border rounded p-2 bg-white">
        <option value="">Todos</option>
        @foreach ($st as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
    </select>
    <button class="border rounded px-4 bg-white">Filtrar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b"><tr><th class="p-3">Cliente</th><th>Serviço</th><th>Preço</th><th>Frequência</th><th>Próxima cobrança</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($subs as $s)
        <tr class="border-b {{ $s->status === 'active' ? '' : 'opacity-60' }}">
            <td class="p-3">{{ $s->customer?->name }} <small class="text-gray-500">{{ trim($s->customer?->block.' '.$s->customer?->unit) }}</small></td>
            <td>{{ $s->service?->name }}</td>
            <td>{{ number_format($s->price, 2, ',', '.') }}</td>
            <td>{{ $freq[$s->frequency] ?? $s->frequency }}@if ($s->interval_count > 1) <small>(a cada {{ $s->interval_count }})</small>@endif</td>
            <td>{{ $s->next_charge_date->format('d/m/Y') }}</td>
            <td>{{ $st[$s->status] ?? $s->status }}</td>
            <td class="text-right pr-3 space-x-3">
                @if ($s->status === 'active')
                    <form method="POST" action="{{ route('subscriptions.status', $s) }}" class="inline" data-confirm="Pausar este contrato?">@csrf @method('PATCH')<input type="hidden" name="status" value="paused"><button class="text-gray-600">Pausar</button></form>
                @elseif ($s->status === 'paused')
                    <form method="POST" action="{{ route('subscriptions.status', $s) }}" class="inline">@csrf @method('PATCH')<input type="hidden" name="status" value="active"><button class="text-blue-600">Retomar</button></form>
                @endif
                @if ($s->status !== 'ended')
                    <form method="POST" action="{{ route('subscriptions.status', $s) }}" class="inline" data-confirm="Terminar o contrato? Deixam de ser geradas cobranças.">@csrf @method('PATCH')<input type="hidden" name="status" value="ended"><button class="text-red-600">Terminar</button></form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="p-4 text-gray-500">Ainda não há contratos.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $subs->links() }}</div>
@endsection
