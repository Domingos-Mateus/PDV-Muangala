@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $user->exists ? 'Editar utilizador' : 'Novo utilizador' }}</h1>
<p class="text-gray-500 mb-4">Loja: {{ $store?->name }}</p>

<form method="POST"
      action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}"
      class="bg-white p-6 rounded shadow max-w-xl space-y-4">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <input type="hidden" name="store_id" value="{{ $storeId }}">

    <div>
        <label class="block text-sm mb-1">Nome</label>
        <input name="name" value="{{ old('name', $user->name) }}" class="w-full border rounded p-2">
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1">Username (para entrar)</label>
            <input name="username" value="{{ old('username', $user->username) }}" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">Código de funcionário</label>
            <input name="employee_code" value="{{ old('employee_code', $user->employee_code) }}" class="w-full border rounded p-2">
        </div>
    </div>

    <div>
        <label class="block text-sm mb-1">Email (opcional)</label>
        <input name="email" type="email" value="{{ old('email', $user->email) }}" class="w-full border rounded p-2">
    </div>

    <div>
        <label class="block text-sm mb-1">Perfil</label>
        <select name="role_id" class="w-full border rounded p-2 bg-white">
            <option value="">Escolher...</option>
            @foreach ($roles as $r)
                <option value="{{ $r->id }}" @selected(old('role_id', $user->role_id) == $r->id)>{{ $r->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm mb-1">Palavra-passe @if($user->exists)<span class="text-gray-400">(deixa vazio para manter)</span>@endif</label>
            <input name="password" type="password" autocomplete="new-password" class="w-full border rounded p-2">
        </div>
        <div>
            <label class="block text-sm mb-1">Repetir palavra-passe</label>
            <input name="password_confirmation" type="password" autocomplete="new-password" class="w-full border rounded p-2">
        </div>
    </div>

    @if ($user->id !== auth()->id())
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1"
                   @checked(old('_token') ? old('is_active') : ($user->exists ? $user->is_active : true))>
            Conta activa
        </label>
    @endif

    <div class="flex gap-3">
        <button class="bg-blue-600 text-white rounded px-4 py-2">Guardar</button>
        <a href="{{ route('users.index', ['store_id' => $storeId]) }}" class="px-4 py-2 text-gray-600">Cancelar</a>
    </div>
</form>
@endsection
