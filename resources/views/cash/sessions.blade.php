@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Turnos de caixa</h1>

@include('partials.store-switcher')

<form method="GET" class="flex gap-2 mb-4">
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <select name="status" class="border rounded p-2 bg-white">
        <option value="">Todos</option>
        <option value="open" @selected(request('status') === 'open')>Abertos</option>
        <option value="closed" @selected(request('status') === 'closed')>Fechados</option>
    </select>
    <button class="border rounded px-4 bg-white">Filtrar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Caixa</th><th>Funcionário</th><th>Abertura</th><th>Fecho</th><th>Esperado</th><th>Contado</th><th>Diferença</th><th>Notas</th></tr>
    </thead>
    <tbody>
    @forelse ($sessions as $s)
        @php $d = (float) $s->difference; @endphp
        <tr class="border-b">
            <td class="p-3">{{ $s->register?->name }}</td>
            <td>{{ $s->user?->name }}</td>
            <td>{{ $s->opened_at->format('d/m/Y H:i') }}</td>
            <td>{{ $s->closed_at?->format('d/m/Y H:i') ?? 'Aberto' }}</td>
            <td>{{ $s->status === 'closed' ? number_format($s->expected_amount, 2, ',', '.') : '-' }}</td>
            <td>{{ $s->status === 'closed' ? number_format($s->counted_amount, 2, ',', '.') : '-' }}</td>
            <td class="font-semibold {{ $s->status === 'closed' && $d != 0 ? ($d < 0 ? 'text-red-600' : 'text-yellow-700') : '' }}">
                {{ $s->status === 'closed' ? number_format($d, 2, ',', '.') : '-' }}
            </td>
            <td>{{ $s->notes }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="p-4 text-gray-500">Ainda não há turnos.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $sessions->links() }}</div>
@endsection
