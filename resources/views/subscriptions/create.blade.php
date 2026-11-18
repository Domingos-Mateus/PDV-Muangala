@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Novo contrato</h1>

@if ($customers->isEmpty() || $services->isEmpty())
    <div class="bg-yellow-100 text-yellow-800 p-4 rounded max-w-xl">Precisas de pelo menos um cliente e um serviço activos nesta loja.</div>
@else
<form method="POST" action="{{ route('subscriptions.store') }}"
      x-data="{
          q: '', customer_id: '', service_id: '', price: '',
          customers: {{ \Illuminate\Support\Js::from($customers) }},
          services: {{ \Illuminate\Support\Js::from($services) }},
          get filtered() { const t = this.q.toLowerCase(); return this.customers.filter(c => c.label.toLowerCase().includes(t)).slice(0, 50) },
          pick() { const s = this.services.find(x => x.id == this.service_id); if (s) this.price = s.default_price }
      }"
      class="bg-white p-6 rounded shadow max-w-xl grid sm:grid-cols-2 gap-4">
    @csrf
    <input type="hidden" name="store_id" value="{{ $storeId }}">

    <div class="sm:col-span-2 text-sm">Cliente
        <input type="text" x-model="q" placeholder="Pesquisar cliente..." class="w-full border rounded p-2 mt-1 mb-1">
        <select name="customer_id" x-model="customer_id" size="5" class="w-full border rounded p-2 bg-white">
            <template x-for="c in filtered" :key="c.id"><option :value="c.id" x-text="c.label"></option></template>
        </select>
    </div>

    <label class="text-sm">Serviço
        <select name="service_id" x-model="service_id" @change="pick()" class="w-full border rounded p-2 mt-1 bg-white">
            <option value="">Escolher...</option>
            <template x-for="s in services" :key="s.id"><option :value="s.id" x-text="s.name"></option></template>
        </select>
    </label>
    <label class="text-sm">Preço deste cliente
        <input name="price" type="number" step="0.01" min="0" x-model="price" class="w-full border rounded p-2 mt-1">
    </label>

    <label class="text-sm">Frequência
        <select name="frequency" class="w-full border rounded p-2 mt-1 bg-white">
            <option value="monthly" selected>Mensal</option>
            <option value="weekly">Semanal</option>
            <option value="daily">Diária</option>
        </select>
    </label>
    <label class="text-sm">A cada (1 = todos os períodos)
        <input name="interval_count" type="number" min="1" max="12" value="1" class="w-full border rounded p-2 mt-1">
    </label>

    <label class="sm:col-span-2 text-sm">Data de início
        <input name="start_date" type="date" value="{{ today()->startOfMonth()->toDateString() }}" class="w-full border rounded p-2 mt-1">
        <small class="text-gray-500">Se for no passado, as cobranças em atraso são geradas já.</small>
    </label>

    <div class="sm:col-span-2 flex gap-3">
        <button class="bg-blue-600 text-white rounded px-4 py-2">Criar contrato</button>
        <a href="{{ route('subscriptions.index', ['store_id' => $storeId]) }}" class="px-4 py-2 text-gray-600">Cancelar</a>
    </div>
</form>
@endif
@endsection
