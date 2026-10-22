@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Perfis e permissões</h1>
    @if ($storeId)
        <a href="{{ route('roles.create', ['store_id' => $storeId]) }}"
           class="bg-blue-600 text-white rounded px-4 py-2">Novo perfil</a>
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
        <tr><th class="p-3">Perfil</th><th>Permissões</th><th>Utilizadores</th><th></th></tr>
    </thead>
    <tbody>
    @forelse ($roles as $r)
        <tr class="border-b">
            <td class="p-3">{{ $r->name }}</td>
            <td>{{ $r->permissions_count }}</td>
            <td>{{ $r->users_count }}</td>
            <td class="text-right pr-3 space-x-3">
                <a class="text-blue-600" href="{{ route('roles.edit', $r) }}">Editar</a>
                @if ($r->users_count === 0)
                    <form method="POST" action="{{ route('roles.destroy', $r) }}" class="inline"
                          data-confirm="Apagar o perfil {{ $r->name }}?">
                        @csrf @method('DELETE')
                        <button class="text-red-600">Apagar</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="4" class="p-4 text-gray-500">Esta loja ainda não tem perfis.</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
