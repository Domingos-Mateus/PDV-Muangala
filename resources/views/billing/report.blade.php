@extends('layouts.app')

@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $labels = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'transfer' => 'Transferência'];
    $link = fn ($from, $to) => route('billing.report', array_merge(request()->query(), ['from' => $from, 'to' => $to, 'page' => null]));
@endphp

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Cobranças</h1>
    <a href="{{ route('billing.export', request()->query()) }}" class="border rounded px-4 py-2 bg-white">Exportar CSV</a>
</div>

{{-- atalhos de período --}}
<div class="flex flex-wrap gap-2 mb-3 text-sm">
    <a class="border rounded px-3 py-1 bg-white" href="{{ $link(today()->toDateString(), today()->toDateString()) }}">Hoje</a>
    <a class="border rounded px-3 py-1 bg-white" href="{{ $link(today()->subDay()->toDateString(), today()->subDay()->toDateString()) }}">Ontem</a>
    <a class="border rounded px-3 py-1 bg-white" href="{{ $link(today()->startOfMonth()->toDateString(), today()->toDateString()) }}">Este mês</a>
    <a class="border rounded px-3 py-1 bg-white" href="{{ $link(today()->subMonthNoOverflow()->startOfMonth()->toDateString(), today()->subMonthNoOverflow()->endOfMonth()->toDateString()) }}">Mês passado</a>
</div>

{{-- filtros --}}
<form method="GET" class="bg-white rounded shadow p-4 flex flex-wrap items-end gap-3 text-sm mb-4">
    @if ($stores->isNotEmpty())
        <label>Loja
            <select name="store_id" class="block border rounded p-2 bg-white">
                @foreach ($stores as $s)<option value="{{ $s->id }}" @selected($s->id == $storeId)>{{ $s->name }}@if($s->address) - {{ $s->address }}@endif</option>@endforeach
            </select>
        </label>
    @else
        <input type="hidden" name="store_id" value="{{ $storeId }}">
    @endif

    <label>De <input type="date" name="from" value="{{ $f['from'] }}" class="block border rounded p-2"></label>
    <label>Até <input type="date" name="to" value="{{ $f['to'] }}" class="block border rounded p-2"></label>

    <label>Recebido por
        <select name="user_id" class="block border rounded p-2 bg-white">
            <option value="">Todos</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected(($f['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>@endforeach
        </select>
    </label>

    <label>Cliente
        <input name="q" value="{{ $f['q'] ?? '' }}" placeholder="Nome, bloco, apt., telefone" class="block border rounded p-2 w-56">
    </label>

    <label>Serviço
        <select name="service_id" class="block border rounded p-2 bg-white">
            <option value="">Todos</option>
            @foreach ($serviceList as $sv)<option value="{{ $sv->id }}" @selected(($f['service_id'] ?? '') == $sv->id)>{{ $sv->name }}</option>@endforeach
        </select>
    </label>

    <label>Pagamento
        <select name="method" class="block border rounded p-2 bg-white">
            <option value="">Todos</option>
            @foreach ($labels as $k => $v)<option value="{{ $k }}" @selected(($f['method'] ?? '') === $k)>{{ $v }}</option>@endforeach
        </select>
    </label>

    <label>Estado
        <select name="status" class="block border rounded p-2 bg-white">
            <option value="">Todos</option>
            <option value="completed" @selected(($f['status'] ?? '') === 'completed')>Concluídos</option>
            <option value="cancelled" @selected(($f['status'] ?? '') === 'cancelled')>Cancelados</option>
        </select>
    </label>

    <button class="bg-blue-600 text-white rounded px-4 py-2">Filtrar</button>
    <a href="{{ route('billing.report', ['store_id' => $storeId]) }}" class="px-3 py-2 text-gray-600">Limpar</a>
</form>

{{-- cartões --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
    <div class="bg-white rounded shadow p-4">
        <div class="text-sm text-gray-500">Total cobrado</div>
        <div class="text-2xl font-bold text-green-700">{{ $fmt($totals['collected']) }}</div>
    </div>
    <div class="bg-white rounded shadow p-4">
        <div class="text-sm text-gray-500">Recibos</div>
        <div class="text-2xl font-bold">{{ $totals['count'] }}</div>
    </div>
    <div class="bg-white rounded shadow p-4">
        <div class="text-sm text-gray-500">Média por recibo</div>
        <div class="text-2xl font-bold">{{ $fmt($totals['avg']) }}</div>
    </div>
    <div class="bg-white rounded shadow p-4">
        <div class="text-sm text-gray-500">Cancelados</div>
        <div class="text-2xl font-bold {{ $totals['cancelled'] ? 'text-red-600' : '' }}">{{ $totals['cancelled'] }}</div>
        @if ($totals['cancelled']) <small class="text-gray-500">{{ $fmt($totals['cancelled_total']) }}</small> @endif
    </div>
    <a href="{{ route('billing.debtors', ['store_id' => $storeId]) }}" class="bg-white rounded shadow p-4 {{ $overdue->owed > 0 ? 'ring-2 ring-red-300' : '' }}">
        <div class="text-sm text-gray-500">Em atraso agora</div>
        <div class="text-2xl font-bold {{ $overdue->owed > 0 ? 'text-red-600' : '' }}">{{ $fmt($overdue->owed) }}</div>
        <small class="text-gray-500">{{ $overdue->customers }} cliente(s)</small>
    </a>
</div>

<div class="grid lg:grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">Quem cobrou</h2>
        @forelse ($collectors as $c)
            <div class="flex justify-between text-sm py-1 border-b"><span>{{ $c->name }} <small class="text-gray-500">({{ $c->n }})</small></span><span>{{ $fmt($c->total) }}</span></div>
        @empty
            <p class="text-sm text-gray-500">Sem recibos.</p>
        @endforelse
    </div>

    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">Formas de pagamento</h2>
        @forelse ($byMethod as $m => $amount)
            <div class="flex justify-between text-sm py-1 border-b"><span>{{ $labels[$m] ?? $m }}</span><span>{{ $fmt($amount) }}</span></div>
        @empty
            <p class="text-sm text-gray-500">Sem recibos.</p>
        @endforelse
    </div>

    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold mb-2">Por serviço</h2>
        @forelse ($byService as $s)
            <div class="flex justify-between text-sm py-1 border-b"><span>{{ $s->name }} <small class="text-gray-500">({{ $s->n }})</small></span><span>{{ $fmt($s->amount) }}</span></div>
        @empty
            <p class="text-sm text-gray-500">Sem recibos.</p>
        @endforelse
    </div>
</div>

@if ($days->count() > 1)
    <div class="bg-white rounded shadow p-4 mb-4">
        <h2 class="font-semibold mb-2">Por dia</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left border-b"><tr><th class="py-2">Dia</th><th>Recibos</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                @foreach ($days as $d)
                    <tr class="border-b"><td class="py-1">{{ \Carbon\Carbon::parse($d->d)->format('d/m/Y') }}</td><td>{{ $d->n }}</td><td class="text-right">{{ $fmt($d->total) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- lista --}}
<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Recibo</th><th>Data</th><th>Cliente</th><th>Recebido por</th><th>Pagamento</th><th class="text-right">Total</th><th>Estado</th><th></th></tr>
    </thead>
    <tbody>
    @forelse ($receipts as $r)
        <tr class="border-b {{ $r->status === 'cancelled' ? 'text-gray-400' : '' }}">
            <td class="p-3">{{ $r->number }}</td>
            <td>{{ $r->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $r->customer?->name }} <small class="text-gray-500">{{ trim($r->customer?->block.' '.$r->customer?->unit) }}</small></td>
            <td>{{ $r->user?->name }}</td>
            <td>{{ $r->payments->pluck('method')->map(fn ($m) => $labels[$m] ?? $m)->implode(' + ') }}</td>
            <td class="text-right">{{ $fmt($r->total) }}</td>
            <td>{{ $r->status === 'cancelled' ? 'Cancelado' : 'Concluído' }}</td>
            <td class="text-right pr-3"><a class="text-blue-600" href="{{ route('billing.show', $r) }}">Ver</a></td>
        </tr>
    @empty
        <tr><td colspan="8" class="p-4 text-gray-500">Nenhum recibo neste período.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $receipts->links() }}</div>
@endsection
