@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Utilizadores</h1>
    @if ($storeId)
        <a href="{{ route('users.create', ['store_id' => $storeId]) }}"
           class="bg-blue-600 text-white rounded px-4 py-2">Novo utilizador</a>
    @endif
</div>

@if ($stores->isNotEmpty())
    <form method="GET" class="mb-4">
        <select name="store_id" onchange="this.form.submit()" class="border rounded p-2 bg-white">
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected($s->id == $storeId)>{{ $s->name }} @if($s->address) - {{ $s->address }} @endif</option>
            @endforeach
        </select>
    </form>
@endif

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Nome</th><th>Username</th><th>Código</th><th>Perfil</th><th>Estado</th><th></th></tr>
    </thead>
    <tbody>
    @forelse ($users as $u)
        <tr class="border-b">
            <td class="p-3">{{ $u->name }}</td>
            <td>{{ $u->username }}</td>
            <td>{{ $u->employee_code ?? '-' }}</td>
            <td>{{ $u->role?->name ?? '-' }}</td>
            <td>
                <span class="{{ $u->is_active ? 'text-green-600' : 'text-red-600' }}">
                    {{ $u->is_active ? 'Activo' : 'Inactivo' }}
                </span>
            </td>
            <td class="text-right pr-3 space-x-3">
                <a class="text-blue-600" href="{{ route('users.edit', $u) }}">Editar</a>
                @if ($u->id !== auth()->id())
                    <form method="POST" action="{{ route('users.toggle', $u) }}" class="inline"
                          data-confirm="{{ $u->is_active ? 'Desactivar' : 'Activar' }} {{ $u->name }}?">
                        @csrf @method('PATCH')
                        <button class="{{ $u->is_active ? 'text-red-600' : 'text-green-600' }}">
                            {{ $u->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-gray-500">Esta loja ainda não tem utilizadores.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
