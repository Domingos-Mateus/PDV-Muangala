@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Estoque</h1>

@include('partials.store-switcher')

<div class="grid sm:grid-cols-3 gap-4 mb-4">
    <div class="bg-white rounded shadow p-4">
        <div class="text-sm text-gray-500">Valor a custo</div>
        <div class="text-xl font-bold">{{ number_format($totals->cost, 2, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded shadow p-4">
        <div class="text-sm text-gray-500">Valor a preço de venda</div>
        <div class="text-xl font-bold">{{ number_format($totals->sale, 2, ',', '.') }}</div>
    </div>
    <a href="{{ route('stock.index', ['store_id' => $storeId, 'low' => 1]) }}"
       class="bg-white rounded shadow p-4 {{ $lowCount ? 'ring-2 ring-red-400' : '' }}">
        <div class="text-sm text-gray-500">Produtos com estoque baixo</div>
        <div class="text-xl font-bold {{ $lowCount ? 'text-red-600' : '' }}">{{ $lowCount }}</div>
    </a>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <input name="q" value="{{ request('q') }}" placeholder="Nome, SKU ou código de barras" class="border rounded p-2 w-72">
    <select name="category_id" class="border rounded p-2 bg-white">
        <option value="">Todas as categorias</option>
        @foreach ($categories as $c)
            <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="low" value="1" @checked(request()->boolean('low'))> Só estoque baixo
    </label>
    <button class="border rounded px-4 bg-white">Filtrar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Produto</th><th>SKU</th><th>Categoria</th><th>Quantidade</th><th>Mínimo</th><th></th></tr>
    </thead>
    <tbody>
    @forelse ($products as $p)
        @php $qty = (float) ($p->stock->qty ?? 0); $min = (float) ($p->stock->min_stock ?? 0); @endphp
        <tr class="border-b {{ $p->is_active ? '' : 'opacity-50' }}">
            <td class="p-3">{{ $p->name }}</td>
            <td>{{ $p->sku }}</td>
            <td>{{ $p->category?->name }}</td>
            <td class="{{ $qty <= $min ? 'text-red-600 font-semibold' : '' }}">
                {{ rtrim(rtrim(number_format($qty, 3, ',', ''), '0'), ',') }} {{ $p->unit }}
                @if ($qty <= $min) <small>(baixo)</small> @endif
            </td>
            <td>{{ rtrim(rtrim(number_format($min, 3, ',', ''), '0'), ',') }}</td>
            <td class="text-right pr-3 space-x-3">
                <a class="text-gray-600" href="{{ route('stock.history', $p) }}">Histórico</a>
                @can('stock.adjust')
                    <a class="text-blue-600" href="{{ route('stock.adjust.form', $p) }}">Ajustar</a>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-gray-500">Nenhum produto encontrado.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $products->links() }}</div>
@endsection
