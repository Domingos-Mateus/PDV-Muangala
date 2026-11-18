@php $fmt = fn ($n) => number_format((float) $n, 2, ',', '.'); @endphp

<div class="grid lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        {{-- pesquisa --}}
        <div class="bg-white rounded shadow p-4 relative">
            @if ($customer)
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-lg font-bold">{{ $customer->name }}</div>
                        <div class="text-sm text-gray-500">{{ trim($customer->block.' '.$customer->unit) }} {{ $customer->phone ? '· '.$customer->phone : '' }}</div>
                    </div>
                    <button type="button" wire:click="clear" class="text-blue-600 text-sm">Trocar cliente</button>
                </div>
            @else
                <input type="text" autofocus autocomplete="off" wire:model.live.debounce.250ms="search"
                       placeholder="Nome, bloco, apartamento ou telefone" class="w-full border rounded p-3 text-lg">
                @if ($results->isNotEmpty())
                    <div class="absolute left-4 right-4 mt-1 bg-white border rounded shadow z-10 divide-y">
                        @foreach ($results as $c)
                            <button type="button" wire:click="pick({{ $c->id }})" class="w-full text-left p-3 hover:bg-gray-50">
                                {{ $c->name }} <small class="text-gray-500">{{ trim($c->block.' '.$c->unit) }} {{ $c->phone }}</small>
                            </button>
                        @endforeach
                    </div>
                @elseif (mb_strlen(trim($search)) >= 2)
                    <p class="text-sm text-gray-500 mt-2">Nenhum cliente encontrado.</p>
                @endif
            @endif
        </div>

        {{-- períodos --}}
        @if ($customer)
            <div class="bg-white rounded shadow">
                <table class="w-full text-sm">
                    <thead class="text-left border-b">
                        <tr><th class="p-3 w-10"></th><th>Período</th><th>Estado</th><th class="text-right">Saldo</th><th class="w-36 text-right pr-3">A pagar</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($charges as $id => $c)
                        @php
                            $overdue = $c->due_date->lt(today());
                            $current = ! $overdue && $c->period_start->lte(today());
                            $on = ! empty($selected[$id]);
                        @endphp
                        <tr class="border-b" wire:key="ch-{{ $id }}">
                            <td class="p-3"><input type="checkbox" wire:model.live="selected.{{ $id }}"></td>
                            <td>{{ $c->description }}</td>
                            <td>
                                @if ($overdue) <span class="text-red-600 font-semibold">Em atraso</span>
                                @elseif ($current) <span class="text-yellow-700">Mês actual</span>
                                @else <span class="text-gray-500">Adiantado</span> @endif
                            </td>
                            <td class="text-right">{{ $fmt($c->balance()) }}@if ($c->paid_amount > 0) <small class="text-gray-500 block">de {{ $fmt($c->amount) }}</small>@endif</td>
                            <td class="text-right pr-3">
                                @if ($on && $overdue)
                                    <input type="text" inputmode="decimal" wire:model.live.debounce.400ms="amounts.{{ $id }}" class="w-28 border rounded p-1 text-right">
                                @elseif ($on)
                                    {{ $fmt($c->balance()) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-gray-500">Este cliente não tem períodos por pagar. Se tiver contratos, usa «Pagar adiantado».</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="p-3 flex items-center justify-between text-sm border-t">
                    <span class="text-gray-500">Só a dívida em atraso pode ser paga em parcelas.</span>
                    <button type="button" wire:click="more" @disabled($ahead >= 12) class="text-blue-600 disabled:opacity-40">+ Pagar adiantado (mais 1 período)</button>
                </div>
            </div>
        @endif
    </div>

    {{-- pagamento --}}
    <div class="space-y-4">
        <div class="bg-white rounded shadow p-4 space-y-3">
            <div class="flex justify-between text-2xl font-bold"><span>Total</span><span>{{ $fmt($t['total']) }}</span></div>
            <div class="text-sm text-gray-500">{{ count($alloc) }} período(s) seleccionado(s)</div>
        </div>

        <div class="bg-white rounded shadow p-4 space-y-3">
            <h2 class="font-semibold">Pagamento</h2>
            <label class="flex items-center justify-between text-sm gap-2">Cartão (TPA)
                <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="card" class="w-32 border rounded p-1 text-right"></label>
            <label class="flex items-center justify-between text-sm gap-2">Transferência
                <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="transfer" class="w-32 border rounded p-1 text-right"></label>
            <label class="flex items-center justify-between text-sm gap-2">Dinheiro entregue
                <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="cash" class="w-32 border rounded p-1 text-right"></label>

            <button type="button" wire:click="exact" class="text-sm text-blue-600">Dinheiro exacto ({{ $fmt(max($t['cashDue'], 0)) }})</button>

            @if ($t['over'])
                <p class="text-sm text-red-600">Cartão e transferência passam do total.</p>
            @elseif ($t['cashDue'] > 0 && $t['missing'] > 0 && $t['total'] > 0)
                <p class="text-sm text-red-600">Faltam {{ $fmt($t['missing']) }}</p>
            @endif

            <div class="flex justify-between text-lg font-bold {{ $t['change'] > 0 ? 'text-green-700' : '' }}"><span>Troco</span><span>{{ $fmt($t['change']) }}</span></div>

            <button type="button" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout" @disabled($t['blocked'])
                    class="w-full bg-green-600 text-white rounded p-3 text-lg disabled:opacity-40">Receber pagamento</button>
        </div>

        <div class="bg-white rounded shadow p-4 text-sm">
            <h2 class="font-semibold mb-2">Os meus últimos recibos</h2>
            @forelse ($recent as $r)
                <div class="flex items-center justify-between py-1 border-b {{ $r->status === 'cancelled' ? 'text-gray-400 line-through' : '' }}">
                    <span>{{ $r->number }} <small class="text-gray-500">{{ $r->customer?->name }}</small></span>
                    <span class="flex items-center gap-3">{{ $fmt($r->total) }}
                        <button type="button" class="text-blue-600" onclick="window.pdvPrint('{{ route('billing.receipt', [$r->id, 'auto' => 1]) }}')">Reimprimir</button>
                    </span>
                </div>
            @empty
                <p class="text-gray-500">Ainda não recebeste nada.</p>
            @endforelse
        </div>
    </div>

    @script
    <script>
        window.pdvPrint = (url) => {
            document.getElementById('receipt-frame')?.remove();
            const f = document.createElement('iframe');
            f.id = 'receipt-frame';
            f.style.cssText = 'position:fixed;width:0;height:0;border:0;right:0;bottom:0';
            f.src = url;
            document.body.appendChild(f);
        };

        $wire.on('collect-notify', (e) => {
            const d = Array.isArray(e) ? e[0] : e;
            Swal.fire({ icon: 'error', title: d.title, text: d.text, confirmButtonColor: '#2563eb' });
        });

        $wire.on('collect-done', (e) => {
            const d = Array.isArray(e) ? e[0] : e;
            Swal.fire({
                icon: 'success', title: 'Recibo ' + d.number,
                html: '<div style="font-size:14px;color:#6b7280">Total ' + d.total + '</div>'
                    + '<div style="font-size:28px;font-weight:700;margin-top:6px">Troco: ' + d.change + '</div>',
                showCancelButton: true, confirmButtonText: 'Imprimir recibo', cancelButtonText: 'Fechar',
                confirmButtonColor: '#16a34a', allowOutsideClick: false,
            }).then((r) => { if (r.isConfirmed) window.pdvPrint(d.url); });
        });
    </script>
    @endscript
</div>
