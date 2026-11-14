@php $fmt = fn ($n) => number_format($n, 2, ',', '.'); @endphp

<div class="grid lg:grid-cols-3 gap-4">
    {{-- esquerda: pesquisa e carrinho --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded shadow p-4 relative">
            <input id="pdv-search" type="text" autofocus autocomplete="off" wire:model.live.debounce.250ms="search"
                wire:keydown.enter="scan($event.target.value)"
                placeholder="Código de barras, SKU ou nome (Enter adiciona)" class="w-full border rounded p-3 text-lg">

            @if ($results->isNotEmpty())
                <div class="absolute left-4 right-4 mt-1 bg-white border rounded shadow z-10 divide-y">
                    @foreach ($results as $p)
                        @php $q = (float) ($p->stock->qty ?? 0); @endphp
                        <button type="button" wire:click="add({{ $p->id }})" @disabled($q <= 0)
                            class="w-full text-left p-3 hover:bg-gray-50 flex justify-between disabled:opacity-40">
                            <span>{{ $p->name }} <small class="text-gray-500">{{ $p->sku }}</small></span>
                            <span class="text-sm">
                                {{ $fmt($p->sale_price) }}
                                <small class="{{ $q <= 0 ? 'text-red-600' : 'text-gray-500' }}">· {{ $q + 0 }}
                                    {{ $p->unit }}</small>
                            </span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white rounded shadow">
            <table class="w-full text-sm">
                <thead class="text-left border-b">
                    <tr>
                        <th class="p-3">Produto</th>
                        <th>Preço</th>
                        <th class="w-44">Quantidade</th>
                        <th class="text-right">Subtotal</th>
                        <th class="w-10"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cart as $id => $i)
                        <tr class="border-b" wire:key="row-{{ $id }}">
                            <td class="p-3">{{ $i['name'] }}</td>
                            <td>{{ $fmt($i['price']) }}</td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <button type="button" wire:click="dec({{ $id }})"
                                        class="border rounded px-2">−</button>
                                    <input type="number" min="0"
                                        step="{{ $i['allow_decimal'] ? '0.001' : '1' }}"
                                        wire:model.blur="cart.{{ $id }}.qty"
                                        class="w-20 border rounded p-1 text-center">
                                    <button type="button" wire:click="inc({{ $id }})"
                                        class="border rounded px-2">+</button>
                                    <small class="text-gray-500">{{ $i['unit'] }}</small>
                                </div>
                            </td>
                            <td class="text-right">
                                {{ $fmt(round((float) str_replace(',', '.', (string) $i['qty']) * $i['price'], 2)) }}
                            </td>
                            <td class="text-right pr-3"><button type="button" wire:click="remove({{ $id }})"
                                    class="text-red-600">✕</button></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-500">Carrinho vazio. Lê um código de
                                barras ou pesquisa um produto.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- direita: totais e pagamento --}}
    <div class="space-y-4">
        <div class="bg-white rounded shadow p-4 space-y-3">
            <div class="flex justify-between text-sm"><span>Subtotal</span><span>{{ $fmt($t['subtotal']) }}</span>
            </div>

            @can('sales.discount')
                <label class="flex items-center justify-between text-sm gap-2">Desconto
                    <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="discount"
                        class="w-32 border rounded p-1 text-right">
                </label>
            @endcan

            <div class="flex justify-between text-2xl font-bold border-t pt-3">
                <span>Total</span><span>{{ $fmt($t['total']) }}</span>
            </div>
        </div>

        <div class="bg-white rounded shadow p-4 space-y-3">
            <h2 class="font-semibold">Pagamento</h2>

            <label class="flex items-center justify-between text-sm gap-2">Cartão (TPA)
                <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="card"
                    class="w-32 border rounded p-1 text-right">
            </label>
            <label class="flex items-center justify-between text-sm gap-2">Transferência
                <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="transfer"
                    class="w-32 border rounded p-1 text-right">
            </label>
            <label class="flex items-center justify-between text-sm gap-2">Dinheiro entregue
                <input type="text" inputmode="decimal" wire:model.live.debounce.300ms="cash"
                    class="w-32 border rounded p-1 text-right">
            </label>

            <button type="button" wire:click="exact" class="text-sm text-blue-600">Dinheiro exacto
                ({{ $fmt(max($t['cashDue'], 0)) }})</button>

            @if ($t['over'])
                <p class="text-sm text-red-600">Cartão e transferência passam do total.</p>
            @elseif ($t['cashDue'] > 0 && $t['missing'] > 0 && $t['total'] > 0)
                <p class="text-sm text-red-600">Faltam {{ $fmt($t['missing']) }}</p>
            @endif

            <div class="flex justify-between text-lg font-bold {{ $t['change'] > 0 ? 'text-green-700' : '' }}">
                <span>Troco</span><span>{{ $fmt($t['change']) }}</span>
            </div>

            <button type="button" wire:click="checkout" wire:loading.attr="disabled" wire:target="checkout"
                @disabled($t['blocked'])
                class="w-full bg-green-600 text-white rounded p-3 text-lg disabled:opacity-40">
                Finalizar venda
            </button>
        </div>

        @if ($lastSaleId)
            <div class="bg-white rounded shadow p-4 text-sm">
                <h2 class="font-semibold mb-2">As minhas últimas vendas</h2>
                @forelse ($recent as $s)
                    <div
                        class="flex items-center justify-between py-1 border-b {{ $s->status === 'cancelled' ? 'text-gray-400 line-through' : '' }}">
                        <span>{{ $s->number }} <small
                                class="text-gray-500">{{ $s->created_at->format('H:i') }}</small></span>
                        <span class="flex items-center gap-3">
                            {{ $fmt($s->total) }}
                            <button type="button" class="text-blue-600 no-underline"
                                onclick="window.pdvPrint('{{ route('sales.receipt', [$s->id, 'auto' => 1]) }}')">Reimprimir</button>
                        </span>
                    </div>
                @empty
                    <p class="text-gray-500">Ainda não fizeste vendas.</p>
                @endforelse
            </div>
        @endif
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

            $wire.on('pdv-sale-done', (e) => {
                const d = Array.isArray(e) ? e[0] : e;
                Swal.fire({
                    icon: 'success',
                    title: 'Venda ' + d.number + ' concluída',
                    html: '<div style="font-size:14px;color:#6b7280">Total ' + d.total + '</div>' +
                        '<div style="font-size:28px;font-weight:700;margin-top:6px">Troco: ' + d.change +
                        '</div>',
                    showCancelButton: true,
                    confirmButtonText: 'Imprimir recibo',
                    cancelButtonText: 'Fechar',
                    confirmButtonColor: '#16a34a',
                    allowOutsideClick: false,
                }).then((r) => {
                    if (r.isConfirmed) window.pdvPrint(d.url);
                    setTimeout(() => document.getElementById('pdv-search')?.focus(), 300);
                });
            });


            $wire.on('pdv-notify', (e) => {
                const d = Array.isArray(e) ? e[0] : e;
                Swal.fire({
                    icon: d.type,
                    title: d.title,
                    text: d.text,
                    confirmButtonColor: '#2563eb',
                    ...(d.type === 'success' ? {
                        timer: 4000,
                        timerProgressBar: true
                    } : {}),
                });
            });
            $wire.on('pdv-focus', () => setTimeout(() => document.getElementById('pdv-search')?.focus(), 50));
        </script>
    @endscript
</div>
