<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Recibo {{ $r->number }}</title>
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

    <h1>{{ $r->store->name }}</h1>
    @if ($r->store->address) <p>{{ $r->store->address }}</p> @endif
    @if ($r->store->phone) <p>Tel. {{ $r->store->phone }}</p> @endif
    <hr>
    <p>Recibo {{ $r->number }}</p>
    <p>{{ $r->created_at->format('d/m/Y H:i') }} · {{ $r->user?->name }}</p>
    <p>Cliente: {{ $r->customer?->name }} {{ trim($r->customer?->block.' '.$r->customer?->unit) }}</p>
    @if ($r->status === 'cancelled') <h2>*** CANCELADO ***</h2> @endif
    <hr>

    <table>
        @foreach ($r->allocations as $a)
            <tr><td>{{ $a->charge?->description }}</td><td class="r">{{ number_format($a->amount, 2, ',', '.') }}</td></tr>
            @if ($a->charge && $a->charge->status === 'pending')
                <tr><td colspan="2">&nbsp;&nbsp;(em dívida: {{ number_format($a->charge->balance(), 2, ',', '.') }})</td></tr>
            @endif
        @endforeach
    </table>
    <hr>

    <table>
        <tr><td><strong>TOTAL</strong></td><td class="r"><strong>{{ number_format($r->total, 2, ',', '.') }}</strong></td></tr>
        @php $labels = ['cash' => 'Dinheiro', 'card' => 'Cartão', 'transfer' => 'Transferência']; @endphp
        @foreach ($r->payments as $p)
            @if ($p->method === 'cash')
                <tr><td>Dinheiro entregue</td><td class="r">{{ number_format($p->tendered, 2, ',', '.') }}</td></tr>
                <tr><td>Troco</td><td class="r">{{ number_format($p->change_given, 2, ',', '.') }}</td></tr>
            @else
                <tr><td>{{ $labels[$p->method] ?? $p->method }}</td><td class="r">{{ number_format($p->amount, 2, ',', '.') }}</td></tr>
            @endif
        @endforeach
    </table>
    <hr>
    <p>Obrigado!</p>
    <p>Documento sem valor fiscal</p>

    @if (request()->boolean('auto'))
    <script>window.addEventListener('load', () => { window.focus(); window.print(); });</script>
    @endif
</body>
</html>
