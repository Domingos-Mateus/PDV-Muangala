@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-1">Fechar turno</h1>
<p class="text-gray-500 mb-4">{{ $session->register->name }} · aberto às {{ $session->opened_at->format('H:i') }}</p>

<form method="POST" action="{{ route('cash.close') }}"
      class="bg-white p-6 rounded shadow max-w-md space-y-4"
      data-confirm="Fechar o turno? Depois não podes registar mais movimentos.">
    @csrf
    <label class="block text-sm">Dinheiro contado na gaveta
        <input name="counted_amount" type="number" step="0.01" min="0" value="{{ old('counted_amount') }}"
               class="w-full border rounded p-2 mt-1" autofocus>
        <small class="text-gray-500">Conta todo o dinheiro, incluindo o fundo de maneio.</small>
    </label>
    <label class="block text-sm">Notas (obrigatórias se houver diferença)
        <input name="notes" value="{{ old('notes') }}" maxlength="500" class="w-full border rounded p-2 mt-1">
    </label>
    <div class="flex gap-3">
        <button class="bg-red-600 text-white rounded px-4 py-2">Fechar turno</button>
        <a href="{{ route('cash.current') }}" class="px-4 py-2 text-gray-600">Cancelar</a>
    </div>
</form>
@endsection
