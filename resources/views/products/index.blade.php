@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Produtos</h1>
    @can('products.create')
        <a href="{{ route('products.create', ['store_id' => $storeId]) }}" class="bg-blue-600 text-white rounded px-4 py-2">Novo produto</a>
    @endcan
</div>

@include('partials.store-switcher')

<form method="GET" class="flex gap-2 mb-4">
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <input name="q" value="{{ request('q') }}" placeholder="Nome, SKU ou código de barras" class="border rounded p-2 w-72">
    <select name="category_id" class="border rounded p-2 bg-white">
        <option value="">Todas as categorias</option>
        @foreach ($categories as $c)
            <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
        @endforeach
    </select>
    <button class="border rounded px-4 bg-white">Filtrar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Produto</th><th>SKU</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Estado</th><th></th></tr>
    </thead>
    <tbody>
    @forelse ($products as $p)
        @php $qty = (float) ($p->stock->qty ?? 0); $min = (float) ($p->stock->min_stock ?? 0); @endphp
        <tr class="border-b {{ $p->is_active ? '' : 'opacity-50' }}">
            <td class="p-3">{{ $p->name }}</td>
            <td>{{ $p->sku }}</td>
            <td>{{ $p->category?->name }}</td>
            <td>{{ number_format($p->sale_price, 2, ',', '.') }}</td>
            <td class="{{ $qty <= $min ? 'text-red-600 font-semibold' : '' }}">
                {{ rtrim(rtrim(number_format($qty, 3, ',', ''), '0'), ',') }} {{ $p->unit }}
                @if ($qty <= $min) <small>(baixo)</small> @endif
            </td>
            <td>{{ $p->is_active ? 'Activo' : 'Inactivo' }}</td>
            <td class="text-right pr-3 space-x-3">
                @can('products.update')
                    <a class="text-blue-600" href="{{ route('products.edit', $p) }}">Editar</a>
                    <form method="POST" action="{{ route('products.toggle', $p) }}" class="inline"
                          data-confirm="{{ $p->is_active ? 'Desactivar' : 'Activar' }} {{ $p->name }}?">
                        @csrf @method('PATCH')
                        <button class="text-gray-600">{{ $p->is_active ? 'Desactivar' : 'Activar' }}</button>
                    </form>
                @endcan
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="p-4 text-gray-500">Nenhum produto encontrado.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $products->links() }}</div>
@endsection
