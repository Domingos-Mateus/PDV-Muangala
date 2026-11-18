@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $customer->exists ? 'Editar cliente' : 'Novo cliente' }}</h1>

<form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}"
      class="bg-white p-6 rounded shadow max-w-xl grid sm:grid-cols-2 gap-4">
    @csrf
    @if ($customer->exists) @method('PUT') @endif
    <input type="hidden" name="store_id" value="{{ $storeId }}">

    <label class="sm:col-span-2 text-sm">Nome
        <input name="name" value="{{ old('name', $customer->name) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Bloco / Rua
        <input name="block" value="{{ old('block', $customer->block) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Apartamento / Casa
        <input name="unit" value="{{ old('unit', $customer->unit) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Telefone
        <input name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">NIF
        <input name="nif" value="{{ old('nif', $customer->nif) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="sm:col-span-2 text-sm">Notas
        <input name="notes" value="{{ old('notes', $customer->notes) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="sm:col-span-2 flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active))> Cliente activo
    </label>

    <div class="sm:col-span-2 flex gap-3">
        <button class="bg-blue-600 text-white rounded px-4 py-2">Guardar</button>
        <a href="{{ route('customers.index', ['store_id' => $storeId]) }}" class="px-4 py-2 text-gray-600">Cancelar</a>
    </div>
</form>
@endsection
