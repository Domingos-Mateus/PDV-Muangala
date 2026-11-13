@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold mb-4">{{ $product->exists ? 'Editar produto' : 'Novo produto' }}</h1>

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}"
        class="bg-white p-6 rounded shadow max-w-2xl grid sm:grid-cols-2 gap-4">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif
        <input type="hidden" name="store_id" value="{{ $storeId }}">

        <label class="sm:col-span-2 text-sm">Nome
            <input name="name" value="{{ old('name', $product->name) }}" class="w-full border rounded p-2 mt-1">
        </label>

        <label class="text-sm">SKU
            <input name="sku" value="{{ old('sku', $product->sku) }}" class="w-full border rounded p-2 mt-1">
        </label>
        <label class="text-sm">Código de barras
            <input name="barcode" value="{{ old('barcode', $product->barcode) }}" class="w-full border rounded p-2 mt-1">
        </label>

        <label class="text-sm">Categoria
            <select name="category_id" class="w-full border rounded p-2 mt-1 bg-white">
                <option value="">Sem categoria</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm">Unidade
            <input name="unit" value="{{ old('unit', $product->unit ?? 'un') }}" class="w-full border rounded p-2 mt-1">
        </label>

        <label class="text-sm">Preço de custo
            <input name="cost_price" type="number" step="0.01" min="0"
                value="{{ old('cost_price', $product->cost_price) }}" class="w-full border rounded p-2 mt-1">
        </label>
        <label class="text-sm">Preço de venda
            <input name="sale_price" type="number" step="0.01" min="0"
                value="{{ old('sale_price', $product->sale_price) }}" class="w-full border rounded p-2 mt-1">
        </label>

        <label class="text-sm">Estoque mínimo (alerta)
            <input name="min_stock" type="number" step="0.001" min="0"
                value="{{ old('min_stock', $product->stock->min_stock ?? 0) }}" class="w-full border rounded p-2 mt-1">
        </label>

        @unless ($product->exists)
            <label class="text-sm">Quantidade inicial
                <input name="initial_qty" type="number" step="0.001" min="0" value="{{ old('initial_qty', 0) }}"
                    class="w-full border rounded p-2 mt-1">
            </label>
        @else
            <div class="text-sm">Estoque actual
                <div class="mt-1 p-2 bg-gray-50 rounded">{{ (float) ($product->stock->qty ?? 0) }} {{ $product->unit }}</div>
                <small class="text-gray-500">Para alterar, usa o ajuste de estoque.</small>
            </div>
        @endunless

        <label class="sm:col-span-2 flex items-start gap-2 text-sm">
            <input type="checkbox" name="allow_decimal" value="1" class="mt-1" @checked(old('allow_decimal', $product->allow_decimal))>
            <span>Vendido a peso ou medida (aceita quantidades decimais, ex.: 1,250 kg)
                <small class="block text-gray-500">Se estiver desmarcado, só se vendem unidades inteiras. Se marcares,
                    lembra-te de pôr a unidade em kg, l, etc.</small>
            </span>
        </label>

        <label class="sm:col-span-2 flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))> Produto activo
        </label>

        <div class="sm:col-span-2 flex gap-3">
            <button class="bg-blue-600 text-white rounded px-4 py-2">Guardar</button>
            <a href="{{ route('products.index', ['store_id' => $storeId]) }}" class="px-4 py-2 text-gray-600">Cancelar</a>
        </div>
    </form>
@endsection
