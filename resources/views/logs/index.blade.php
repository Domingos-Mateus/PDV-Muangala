@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Registo de actividade</h1>

<form method="GET" class="flex flex-wrap gap-2 mb-4 text-sm">
    @if ($stores->isNotEmpty())
        <select name="store_id" class="border rounded p-2 bg-white">
            <option value="">Todas as lojas</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected(request('store_id') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    @endif
    <select name="user_id" class="border rounded p-2 bg-white">
        <option value="">Todos os utilizadores</option>
        @foreach ($users as $u)
            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
        @endforeach
    </select>
    <select name="action" class="border rounded p-2 bg-white">
        <option value="">Todas as acções</option>
        @foreach ($labels as $k => $l)
            <option value="{{ $k }}" @selected(request('action') === $k)>{{ $l }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="border rounded p-2">
    <input type="date" name="to" value="{{ request('to') }}" class="border rounded p-2">
    <button class="bg-blue-600 text-white rounded px-4">Filtrar</button>
    <a href="{{ route('logs.index') }}" class="px-3 py-2 text-gray-600">Limpar</a>
</form>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Data</th><th>Utilizador</th><th>Loja</th><th>Acção</th><th>Detalhe</th><th>IP</th></tr>
    </thead>
    <tbody>
    @forelse ($logs as $l)
        <tr class="border-b align-top">
            <td class="p-3 whitespace-nowrap">{{ $l->created_at->format('d/m/Y H:i:s') }}</td>
            <td>{{ $l->user?->name ?? ($l->properties['username'] ?? '-') }}</td>
            <td>{{ $l->store?->name ?? '-' }}</td>
            <td>
                <span class="{{ $l->action === 'login_failed' || $l->action === 'deleted' ? 'text-red-600' : '' }}">
                    {{ $labels[$l->action] ?? $l->action }}
                </span>
            </td>
            <td>
                {{ $l->description }}
                @if ($l->properties && $l->action !== 'login_failed')
                    <details class="text-xs text-gray-600 mt-1">
                        <summary class="cursor-pointer text-blue-600">ver alterações</summary>
                        <pre class="whitespace-pre-wrap">{{ json_encode($l->properties, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                    </details>
                @endif
            </td>
            <td class="pr-3">{{ $l->ip }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-gray-500">Sem registos.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection