@extends('layouts.app')

@section('content')
@php $current = (float) ($product->stock->qty ?? 0); @endphp

<h1 class="text-2xl font-bold mb-1">Ajustar estoque</h1>
<p class="text-gray-500 mb-4">{{ $product->name }} · actual: <strong>{{ $current }} {{ $product->unit }}</strong></p>

<form method="POST" action="{{ route('stock.adjust', $product) }}"
      class="bg-white p-6 rounded shadow max-w-lg space-y-4">
    @csrf

    <label class="block text-sm">Tipo de ajuste
        <select name="mode" class="w-full border rounded p-2 mt-1 bg-white">
            <option value="in"    @selected(old('mode') === 'in')>Entrada (somar)</option>
            <option value="out"   @selected(old('mode') === 'out')>Saída (perda, quebra, consumo)</option>
            <option value="count" @selected(old('mode') === 'count')>Contagem (quantidade real na prateleira)</option>
        </select>
    </label>

    <label class="block text-sm">Quantidade
        <input name="qty" type="number" step="0.001" min="0" value="{{ old('qty') }}" class="w-full border rounded p-2 mt-1">
    </label>

    <label class="block text-sm">Motivo (obrigatório)
        <input name="note" value="{{ old('note') }}" maxlength="200" placeholder="Ex.: caixa danificada, contagem do inventário"
               class="w-full border rounded p-2 mt-1">
    </label>

    <div class="flex gap-3">
        <button class="bg-blue-600 text-white rounded px-4 py-2">Aplicar</button>
        <a href="{{ route('stock.index', ['store_id' => $product->store_id]) }}" class="px-4 py-2 text-gray-600">Cancelar</a>
    </div>
</form>
@endsection
