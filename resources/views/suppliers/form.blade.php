@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $supplier->exists ? 'Editar fornecedor' : 'Novo fornecedor' }}</h1>

<form method="POST"
      action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}"
      class="bg-white p-6 rounded shadow max-w-xl grid sm:grid-cols-2 gap-4">
    @csrf
    @if ($supplier->exists) @method('PUT') @endif
    <input type="hidden" name="store_id" value="{{ $storeId }}">

    <label class="sm:col-span-2 text-sm">Nome
        <input name="name" value="{{ old('name', $supplier->name) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">NIF
        <input name="nif" value="{{ old('nif', $supplier->nif) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="text-sm">Telefone
        <input name="phone" value="{{ old('phone', $supplier->phone) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="sm:col-span-2 text-sm">Email
        <input name="email" type="email" value="{{ old('email', $supplier->email) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="sm:col-span-2 text-sm">Endereço
        <input name="address" value="{{ old('address', $supplier->address) }}" class="w-full border rounded p-2 mt-1">
    </label>
    <label class="sm:col-span-2 flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active))> Fornecedor activo
    </label>

    <div class="sm:col-span-2 flex gap-3">
        <button class="bg-blue-600 text-white rounded px-4 py-2">Guardar</button>
        <a href="{{ route('suppliers.index', ['store_id' => $storeId]) }}" class="px-4 py-2 text-gray-600">Cancelar</a>
    </div>
</form>
@endsection
