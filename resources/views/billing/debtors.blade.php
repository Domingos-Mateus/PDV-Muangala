@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Devedores</h1>

@include('partials.store-switcher')

<div class="bg-white rounded shadow p-4 mb-4 inline-block">
    <div class="text-sm text-gray-500">Total em atraso</div>
    <div class="text-2xl font-bold text-red-600">{{ number_format($totalOwed, 2, ',', '.') }}</div>
</div>

<form method="GET" class="flex gap-2 mb-4">
    <input type="hidden" name="store_id" value="{{ $storeId }}">
    <input name="q" value="{{ request('q') }}" placeholder="Nome, bloco, apartamento ou telefone" class="border rounded p-2 w-80">
    <button class="border rounded px-4 bg-white">Pesquisar</button>
</form>

<table class="w-full bg-white rounded shadow text-sm">
    <thead class="text-left border-b">
        <tr><th class="p-3">Cliente</th><th>Bloco / Apt.</th><th>Telefone</th><th>Períodos</th><th>Mais antigo</th><th class="text-right pr-3">Deve</th></tr>
    </thead>
    <tbody>
    @forelse ($debtors as $d)
        <tr class="border-b">
            <td class="p-3">{{ $d->name }}</td>
            <td>{{ trim($d->block.' '.$d->unit) }}</td>
            <td>{{ $d->phone }}</td>
            <td>{{ $d->n }}</td>
            <td>{{ \Carbon\Carbon::parse($d->oldest)->format('d/m/Y') }}</td>
            <td class="text-right pr-3 font-semibold text-red-600">{{ number_format($d->owed, 2, ',', '.') }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="p-4 text-gray-500">Ninguém em atraso.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4">{{ $debtors->links() }}</div>
@endsection
