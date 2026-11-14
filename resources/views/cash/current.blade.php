@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Caixa</h1>

@if (! $session)
    <form method="POST" action="{{ route('cash.open') }}" class="bg-white p-6 rounded shadow max-w-md space-y-4">
        @csrf
        <h2 class="font-semibold">Abrir turno</h2>

        @if ($registers->isEmpty())
            <p class="text-sm text-gray-600">Não há caixas livres. Pede ao gerente para criar um caixa, ou espera que o turno actual seja fechado.</p>
        @else
            <label class="block text-sm">Caixa
                <select name="cash_register_id" class="w-full border rounded p-2 mt-1 bg-white">
                    @foreach ($registers as $r)
                        <option value="{{ $r->id }}" @selected(old('cash_register_id') == $r->id)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">Dinheiro na gaveta (fundo de maneio)
                <input name="opening_amount" type="number" step="0.01" min="0" value="{{ old('opening_amount', 0) }}"
                       class="w-full border rounded p-2 mt-1">
            </label>
            <button class="bg-blue-600 text-white rounded px-4 py-2">Abrir turno</button>
        @endif
    </form>
@else
    <div class="bg-white p-6 rounded shadow max-w-2xl space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold">{{ $session->register->name }} · turno aberto</h2>
                <p class="text-sm text-gray-500">Desde {{ $session->opened_at->format('d/m/Y H:i') }}</p>
            </div>
            <a href="{{ route('cash.close.form') }}" class="bg-red-600 text-white rounded px-4 py-2">Fechar turno</a>
        </div>

        <div class="grid grid-cols-3 gap-4 text-sm">
            <div><div class="text-gray-500">Abertura</div><div class="font-bold">{{ number_format($summary['opening'], 2, ',', '.') }}</div></div>
            <div><div class="text-gray-500">Suprimentos</div><div class="font-bold">{{ number_format($summary['supplies'], 2, ',', '.') }}</div></div>
            <div><div class="text-gray-500">Sangrias</div><div class="font-bold">{{ number_format($summary['withdrawals'], 2, ',', '.') }}</div></div>
        </div>

        <form method="POST" action="{{ route('cash.movement') }}" class="grid sm:grid-cols-4 gap-2 border-t pt-4">
            @csrf
            <select name="type" class="border rounded p-2 bg-white">
                <option value="supply">Suprimento (entra)</option>
                <option value="withdrawal">Sangria (sai)</option>
            </select>
            <input name="amount" type="number" step="0.01" min="0.01" placeholder="Valor" class="border rounded p-2">
            <input name="reason" placeholder="Motivo" maxlength="200" class="border rounded p-2 sm:col-span-1">
            <button class="border rounded px-4 bg-white">Registar</button>
        </form>

        <table class="w-full text-sm">
            <thead class="text-left border-b"><tr><th class="py-2">Hora</th><th>Tipo</th><th>Valor</th><th>Motivo</th><th>Por</th></tr></thead>
            <tbody>
            @forelse ($movements as $m)
                <tr class="border-b">
                    <td class="py-2">{{ $m->created_at->format('H:i') }}</td>
                    <td>{{ $m->type === 'supply' ? 'Suprimento' : 'Sangria' }}</td>
                    <td class="{{ $m->type === 'supply' ? 'text-green-700' : 'text-red-600' }}">{{ number_format($m->amount, 2, ',', '.') }}</td>
                    <td>{{ $m->reason }}</td>
                    <td>{{ $m->user?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-3 text-gray-500">Sem movimentos neste turno.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
