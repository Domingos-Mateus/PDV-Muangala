@extends('layouts.app')

@section('content')
<h1 class="text-2xl font-bold mb-4">Painel</h1>

@can('sales.view')
    @livewire('sales-dashboard')
@else
    <p class="text-gray-600">Sessão iniciada com sucesso.</p>
@endcan
@endsection
