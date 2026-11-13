@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Categorias</h1>

@include('partials.store-switcher')

<form method="POST" action="{{ route('categories.store') }}" class="flex gap-2 mb-4">
    @csrf
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <input name="name" placeholder="Nova categoria" class="border rounded p-2 w-64">
    <button class="bg-blue-600 text-white rounded px-4">Adicionar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm max-w-2xl">
    <thead class="text-left border-b"><tr><th class="p-3">Nome</th><th>Produtos</th><th></th></tr></thead>
    <tbody>
    @forelse ($categories as $c)
        <tr class="border-b">
            <td class="p-3">
                <form method="POST" action="{{ route('categories.update', $c) }}" class="flex gap-2">
                    @csrf @method('PUT')
                    <input name="name" value="{{ $c->name }}" class="border rounded p-1">
                    <button class="text-blue-600">Guardar</button>
                </form>
            </td>
            <td>{{ $c->products_count }}</td>
            <td class="text-right pr-3">
                <form method="POST" action="{{ route('categories.destroy', $c) }}" class="inline"
                      data-confirm="Apagar a categoria {{ $c->name }}? Os produtos ficam sem categoria.">
                    @csrf @method('DELETE')
                    <button class="text-red-600">Apagar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="3" class="p-4 text-gray-500">Ainda não há categorias.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
