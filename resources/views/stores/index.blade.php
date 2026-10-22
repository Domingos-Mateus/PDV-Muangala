@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Lojas</h1>
    <a href="{{ route('stores.create') }}" class="bg-blue-600 text-white rounded px-4 py-2">Nova loja</a>
</div>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Nome</th><th>Telefone</th><th>Utilizadores</th><th>Estado</th><th></th></tr>
    </thead>
    <tbody>
    @foreach ($stores as $s)
        <tr class="border-b">
            <td class="p-3">{{ $s->name }}</td>
            <td>{{ $s->phone }}</td>
            <td>{{ $s->users_count }}</td>
            <td>{{ $s->is_active ? 'Activa' : 'Inactiva' }}</td>
            <td class="text-right pr-3"><a class="text-blue-600" href="{{ route('stores.edit', $s) }}">Editar</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
