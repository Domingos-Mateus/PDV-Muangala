@extends('layouts.app')

@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $labels = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'transfer' => 'Transferência'];
@endphp

@section('content')
<h1 class="text-2xl font-bold mb-4">Vendas</h1>

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

    @if ($users->isNotEmpty())
        <label>Funcionário
            <select name="user_id" class="block border rounded p-2 bg-white">
                <option value="">Todos</option>
                @foreach ($users as $u)<option value="{{ $u->id }}" @selected(($f['user_id'] ?? '') == $u->id)>{{ $u->name }}</option>@endforeach
            </select>
        </label>
    @endif

    <label>Produto
        <select name="product_id" class="block border rounded p-2 bg-white max-w-xs">
            <option value="">Todos</option>
            @foreach ($products as $p)<option value="{{ $p->id }}" @selected(($f['product_id'] ?? '') == $p->id)>{{ $p->name }}</option>@endforeach
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
            <option value="completed" @selected(($f['status'] ?? '') === 'completed')>Concluídas</option>
            <option value="cancelled" @selected(($f['status'] ?? '') === 'cancelled')>Canceladas</option>
        </select>
    </label>

    <button class="bg-blue-600 text-white rounded px-4 py-2">Filtrar</button>
</form>

<div class="grid sm:grid-cols-4 gap-4 mb-4">
    <div class="bg-white rounded shadow p-4"><div class="text-sm text-gray-500">Vendas concluídas</div><div class="text-xl font-bold">{{ $totals['count'] }}</div></div>
    <div class="bg-white rounded shadow p-4"><div class="text-sm text-gray-500">Total vendido</div><div class="text-xl font-bold">{{ $fmt($totals['revenue']) }}</div></div>
    @can('reports.view')
        <div class="bg-white rounded shadow p-4"><div class="text-sm text-gray-500">Lucro</div><div class="text-xl font-bold">{{ $fmt($totals['profit']) }}</div></div>
    @endcan
    <div class="bg-white rounded shadow p-4"><div class="text-sm text-gray-500">Ticket médio</div><div class="text-xl font-bold">{{ $fmt($totals['avg']) }}</div></div>
</div>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Nº</th><th>Data</th><th>Funcionário</th><th>Pagamento</th><th class="text-right">Total</th><th>Estado</th><th></th></tr>
    </thead>
    <tbody>
    @forelse ($sales as $s)
        <tr class="border-b {{ $s->status === 'cancelled' ? 'text-gray-400' : '' }}">
            <td class="p-3">{{ $s->number }}</td>
            <td>{{ $s->created_at->format('d/m/Y H:i') }}</td>
            <td>{{ $s->user?->name }}</td>
            <td>{{ $s->payments->pluck('method')->map(fn ($m) => $labels[$m] ?? $m)->implode(' + ') }}</td>
            <td class="text-right">{{ $fmt($s->total) }}</td>
            <td>{{ $s->status === 'cancelled' ? 'Cancelada' : 'Concluída' }}</td>
            <td class="text-right pr-3"><a class="text-blue-600" href="{{ route('sales.show', $s) }}">Ver</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="p-4 text-gray-500">Nenhuma venda encontrada.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $sales->links() }}</div>
@endsection
