@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Nova compra</h1>

@if ($suppliers->isEmpty() || $products->isEmpty())
    <div class="bg-yellow-100 text-yellow-800 p-4 rounded max-w-xl">
        Para registar uma compra precisas de pelo menos um fornecedor activo e um produto activo nesta loja.
    </div>
@else
<form method="POST" action="{{ route('purchases.store') }}"
      x-data='{
          products: @json($products),
          rows: [{ product_id: "", qty: 1, unit_cost: 0 }],
          add() { this.rows.push({ product_id: "", qty: 1, unit_cost: 0 }) },
          remove(i) { if (this.rows.length > 1) this.rows.splice(i, 1) },
          pick(row) {
              const p = this.products.find(x => x.id == row.product_id);
              if (p) row.unit_cost = parseFloat(p.cost_price);
          },
          sub(row) { return (parseFloat(row.qty) || 0) * (parseFloat(row.unit_cost) || 0) },
          get total() { return this.rows.reduce((s, r) => s + this.sub(r), 0) },
          fmt(n) { return n.toLocaleString("pt-PT", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }
      }'
      class="bg-white p-6 rounded shadow max-w-4xl space-y-4">
    @csrf
    <input type="hidden" name="store_id" value="{{ $storeId }}">

    <label class="block text-sm">Fornecedor
        <select name="supplier_id" class="w-full max-w-md border rounded p-2 mt-1 bg-white">
            <option value="">Escolher...</option>
            @foreach ($suppliers as $s)
                <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </label>

    <table class="w-full text-sm">
        <thead class="text-left border-b">
            <tr><th class="py-2">Produto</th><th class="w-28">Quantidade</th><th class="w-32">Custo unit.</th><th class="w-32 text-right">Subtotal</th><th class="w-10"></th></tr>
        </thead>
        <tbody>
            <template x-for="(row, i) in rows" :key="i">
                <tr class="border-b">
                    <td class="py-2 pr-2">
                        <select :name="`items[${i}][product_id]`" x-model="row.product_id" @change="pick(row)"
                                class="w-full border rounded p-2 bg-white">
                            <option value="">Escolher...</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="`${p.name} (${p.sku})`"></option>
                            </template>
                        </select>
                    </td>
                    <td class="pr-2">
                        <input type="number" step="0.001" min="0.001" :name="`items[${i}][qty]`" x-model="row.qty"
                               class="w-full border rounded p-2">
                    </td>
                    <td class="pr-2">
                        <input type="number" step="0.01" min="0" :name="`items[${i}][unit_cost]`" x-model="row.unit_cost"
                               class="w-full border rounded p-2">
                    </td>
                    <td class="text-right" x-text="fmt(sub(row))"></td>
                    <td class="text-right">
                        <button type="button" @click="remove(i)" class="text-red-600">✕</button>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>

    <div class="flex items-center justify-between">
        <button type="button" @click="add()" class="text-blue-600">+ Adicionar produto</button>
        <div class="text-lg font-bold">Total: <span x-text="fmt(total)"></span></div>
    </div>

    <label class="block text-sm">Notas (opcional)
        <input name="notes" value="{{ old('notes') }}" maxlength="500" class="w-full border rounded p-2 mt-1">
    </label>

    <div class="flex gap-3">
        <button class="bg-blue-600 text-white rounded px-4 py-2">Registar compra</button>
        <a href="{{ route('purchases.index', ['store_id' => $storeId]) }}" class="px-4 py-2 text-gray-600">Cancelar</a>
    </div>
</form>
@endif
@endsection
