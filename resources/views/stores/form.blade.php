@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-bold mb-4">{{ $store->exists ? 'Editar loja' : 'Nova loja' }}</h1>

    <form method="POST" action="{{ $store->exists ? route('stores.update', $store) : route('stores.store') }}"
        class="bg-white p-6 rounded shadow max-w-lg space-y-4">
        @csrf
        @if ($store->exists)
            @method('PUT')
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-700 p-2 rounded text-sm">{{ $errors->first() }}</div>
        @endif

        <input name="name" value="{{ old('name', $store->name) }}" placeholder="Nome da loja"
            class="w-full border rounded p-2">
        <input name="address" value="{{ old('address', $store->address) }}" placeholder="Endereço"
            class="w-full border rounded p-2">
        <input name="phone" value="{{ old('phone', $store->phone) }}" placeholder="Telefone"
            class="w-full border rounded p-2">

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $store->is_active))> Loja activa
        </label>

        <div class="flex gap-3">
            <button class="bg-blue-600 text-white rounded px-4 py-2">Guardar</button>
            <a href="{{ route('stores.index') }}" class="px-4 py-2 text-gray-600">Cancelar</a>
        </div>
    </form>
@endsection
