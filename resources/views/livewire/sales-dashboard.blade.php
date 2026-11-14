@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $labels = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'transfer' => 'Transferência'];
@endphp

<div wire:poll.5s class="space-y-4">
    @if ($empty)
        <div class="bg-yellow-100 text-yellow-800 p-4 rounded">Ainda não há lojas. Cria a primeira em <strong>Lojas</strong>.</div>
    @else
        {{-- filtros --}}
        <div class="bg-white rounded shadow p-4 flex flex-wrap items-end gap-3 text-sm">
            @if ($stores->isNotEmpty())
                <label>Loja
                    <select wire:model.live="storeId" class="block border rounded p-2 bg-white">
                        @foreach ($stores as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}@if($s->address) - {{ $s->address }}@endif</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <label>De <input type="date" wire:model.live="from" class="block border rounded p-2"></label>
            <label>Até <input type="date" wire:model.live="to" class="block border rounded p-2"></label>

            @if ($isBoss)
                <label>Funcionário
                    <select wire:model.live="userId" class="block border rounded p-2 bg-white">
                        <option value="">Todos</option>
                        @foreach ($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
                    </select>
                </label>
            @endif

            <label>Produto
                <select wire:model.live="productId" class="block border rounded p-2 bg-white max-w-xs">
                    <option value="">Todos</option>
                    @foreach ($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
            </label>

            <div class="flex gap-2 ml-auto">
                <button type="button" wire:click="period('today')" class="border rounded px-3 py-2">Hoje</button>
                <button type="button" wire:click="period('yesterday')" class="border rounded px-3 py-2">Ontem</button>
                <button type="button" wire:click="period('month')" class="border rounded px-3 py-2">Este mês</button>
                <button type="button" wire:click="period('lastmonth')" class="border rounded px-3 py-2">Mês passado</button>
            </div>
        </div>

        {{-- cartões --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded shadow p-4">
                <div class="text-sm text-gray-500">Vendas</div>
                <div class="text-2xl font-bold">{{ $totals['count'] }}</div>
                @if ($totals['cancelled'])<small class="text-red-600">{{ $totals['cancelled'] }} cancelada(s)</small>@endif
            </div>
            <div class="bg-white rounded shadow p-4">
                <div class="text-sm text-gray-500">Total vendido</div>
                <div class="text-2xl font-bold">{{ $fmt($totals['revenue']) }}</div>
            </div>
            @if ($isBoss)
                <div class="bg-white rounded shadow p-4">
                    <div class="text-sm text-gray-500">Lucro</div>
                    <div class="text-2xl font-bold {{ $totals['profit'] < 0 ? 'text-red-600' : 'text-green-700' }}">{{ $fmt($totals['profit']) }}</div>
                    <small class="text-gray-500">Custo: {{ $fmt($totals['cost']) }}</small>
                </div>
            @endif
            <div class="bg-white rounded shadow p-4">
                <div class="text-sm text-gray-500">Ticket médio</div>
                <div class="text-2xl font-bold">{{ $fmt($totals['avg']) }}</div>
            </div>
            <a href="{{ route('stock.index', ['store_id' => $f['store_id'], 'low' => 1]) }}"
               class="bg-white rounded shadow p-4 {{ $lowStock ? 'ring-2 ring-red-400' : '' }}">
                <div class="text-sm text-gray-500">Estoque baixo</div>
                <div class="text-2xl font-bold {{ $lowStock ? 'text-red-600' : '' }}">{{ $lowStock }}</div>
            </a>
        </div>

        <div class="grid lg:grid-cols-3 gap-4">
            {{-- pagamentos --}}
            <div class="bg-white rounded shadow p-4">
                <h2 class="font-semibold mb-2">Formas de pagamento</h2>
                @forelse ($payments as $m => $amount)
                    <div class="flex justify-between text-sm py-1 border-b"><span>{{ $labels[$m] ?? $m }}</span><span>{{ $fmt($amount) }}</span></div>
                @empty
                    <p class="text-sm text-gray-500">Sem vendas.</p>
                @endforelse
            </div>

            {{-- funcionários --}}
            @if ($isBoss)
                <div class="bg-white rounded shadow p-4">
                    <h2 class="font-semibold mb-2">Por funcionário</h2>
                    @forelse ($employees as $e)
                        <div class="flex justify-between text-sm py-1 border-b">
                            <span>{{ $e->name }} <small class="text-gray-500">({{ $e->n }})</small></span>
                            <span>{{ $fmt($e->total) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Sem vendas.</p>
                    @endforelse
                </div>
            @endif

            {{-- produtos --}}
            <div class="bg-white rounded shadow p-4">
                <h2 class="font-semibold mb-2">Produtos mais vendidos</h2>
                @forelse ($top as $p)
                    <div class="flex justify-between text-sm py-1 border-b">
                        <span>{{ $p->product_name }} <small class="text-gray-500">× {{ (float) $p->qty + 0 }}</small></span>
                        <span>{{ $fmt($p->revenue) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Sem vendas.</p>
                @endforelse
            </div>
        </div>

        {{-- por dia --}}
        @if ($days->count() > 1)
            <div class="bg-white rounded shadow p-4">
                <h2 class="font-semibold mb-2">Vendas por dia</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left border-b"><tr><th class="py-2">Dia</th><th>Vendas</th><th class="text-right">Total</th></tr></thead>
                        <tbody>
                        @foreach ($days as $d)
                            <tr class="border-b">
                                <td class="py-1">{{ \Carbon\Carbon::parse($d->d)->format('d/m/Y') }}</td>
                                <td>{{ $d->n }}</td>
                                <td class="text-right">{{ $fmt($d->total) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- últimas vendas --}}
        <div class="bg-white rounded shadow p-4">
            <div class="flex items-center justify-between mb-2">
                <h2 class="font-semibold">Últimas vendas</h2>
                <a href="{{ route('sales.index', ['store_id' => $f['store_id'], 'from' => $f['from'], 'to' => $f['to']]) }}" class="text-sm text-blue-600">Ver todas</a>
            </div>
            <table class="w-full text-sm">
                <thead class="text-left border-b"><tr><th class="py-2">Nº</th><th>Hora</th><th>Funcionário</th><th class="text-right">Total</th><th></th></tr></thead>
                <tbody>
                @forelse ($recent as $s)
                    <tr class="border-b {{ $s->status === 'cancelled' ? 'text-gray-400 line-through' : '' }}">
                        <td class="py-1">{{ $s->number }}</td>
                        <td>{{ $s->created_at->format('d/m H:i') }}</td>
                        <td>{{ $s->user?->name }}</td>
                        <td class="text-right">{{ $fmt($s->total) }}</td>
                        <td class="text-right"><a class="text-blue-600 no-underline" href="{{ route('sales.show', $s->id) }}">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-3 text-gray-500">Ainda não há vendas neste período.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
