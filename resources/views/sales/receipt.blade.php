<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $sale->number }}</title>
    <style>
        body { font-family: monospace; font-size: 12px; width: 76mm; margin: 0 auto; padding: 4mm; color: #000; }
        h1, h2, p { margin: 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 1px 0; vertical-align: top; }
        .r { text-align: right; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        .no-print { text-align: center; margin-bottom: 8px; }
        @media print { .no-print { display: none; } @page { margin: 0; } }
    </style>
</head>
<body>
    <div class="no-print"><button onclick="window.print()">Imprimir</button></div>

    <h1>{{ $sale->store->name }}</h1>
    @if ($sale->store->address) <p>{{ $sale->store->address }}</p> @endif
    @if ($sale->store->phone) <p>Tel. {{ $sale->store->phone }}</p> @endif
    <hr>
    <p>Recibo {{ $sale->number }}</p>
    <p>{{ $sale->created_at->format('d/m/Y H:i') }} · {{ $sale->user?->name }}</p>
    @if ($sale->status === 'cancelled') <h2>*** CANCELADA ***</h2> @endif
    <hr>

    <table>
        @foreach ($sale->items as $i)
            <tr><td colspan="2">{{ $i->product_name }}</td></tr>
            <tr>
                <td>{{ (float) $i->qty + 0 }} x {{ number_format($i->unit_price, 2, ',', '.') }}</td>
                <td class="r">{{ number_format($i->subtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>
    <hr>

    <table>
        @if ($sale->discount > 0)
            <tr><td>Subtotal</td><td class="r">{{ number_format($sale->subtotal, 2, ',', '.') }}</td></tr>
            <tr><td>Desconto</td><td class="r">-{{ number_format($sale->discount, 2, ',', '.') }}</td></tr>
        @endif
        <tr><td><strong>TOTAL</strong></td><td class="r"><strong>{{ number_format($sale->total, 2, ',', '.') }}</strong></td></tr>
        @php $labels = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'transfer' => 'Transferência']; @endphp
        @foreach ($sale->payments as $p)
            @if ($p->method === 'cash')
                <tr><td>Dinheiro entregue</td><td class="r">{{ number_format($p->tendered, 2, ',', '.') }}</td></tr>
                <tr><td>Troco</td><td class="r">{{ number_format($p->change_given, 2, ',', '.') }}</td></tr>
            @else
                <tr><td>{{ $labels[$p->method] ?? $p->method }}</td><td class="r">{{ number_format($p->amount, 2, ',', '.') }}</td></tr>
            @endif
        @endforeach
    </table>
    <hr>
    <p>Obrigado pela preferência!</p>
    <p>Documento sem valor fiscal</p>

    @if (request()->boolean('auto'))
<script>
    window.addEventListener('load', () => { window.focus(); window.print(); });
</script>
@endif
</body>
</html>
