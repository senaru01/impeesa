@php
    $rp = fn (int $value): string => number_format($value, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $sale->number }}</title>
    <meta name="robots" content="noindex">
    <style>
        @page { size: 58mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: #000; font: 11px/1.35 'Courier New', ui-monospace, monospace; }
        .receipt { width: 58mm; margin: 16px auto; padding: 4mm 3mm 6mm; background: #fff; }
        .center { text-align: center; }
        .brand { font-size: 14px; font-weight: 700; letter-spacing: .08em; }
        .muted { font-size: 10px; }
        .rule { margin: 6px 0; border-top: 1px dashed #000; }
        .row { display: flex; justify-content: space-between; gap: 6px; }
        .row span:last-child { flex: none; text-align: right; }
        .group { margin-top: 4px; font-weight: 700; }
        .line { padding-left: 8px; font-size: 10px; }
        .total { font-size: 13px; font-weight: 700; }
        .qris { margin: 4px auto 2px; text-align: center; }
        .qris svg { display: block; width: 44mm; height: 44mm; margin: 4px auto; }
        .qris strong { display: block; font-size: 13px; }
        .actions { display: flex; justify-content: center; gap: 8px; margin: 16px auto; }
        .actions button, .actions a { padding: 8px 16px; border: 1px solid #1e3a8a; border-radius: 999px; background: #1e3a8a; color: #fff; font: 600 13px system-ui, sans-serif; text-decoration: none; cursor: pointer; }
        .actions a { background: #fff; color: #1e3a8a; }
        @media print {
            body { background: #fff; }
            .receipt { margin: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" onclick="window.print()">Cetak</button>
        <a href="{{ route('admin.store.pos') }}">Kembali ke kasir</a>
    </div>

    <div class="receipt">
        <div class="center">
            <div class="brand">IMPEESA 3D PRINT</div>
            <div class="muted">Souvenir 3D Print &middot; Gantungan Kunci Clicky</div>
        </div>
        <div class="rule"></div>
        <div class="row"><span>No</span><span>{{ $sale->number }}</span></div>
        <div class="row"><span>Tgl</span><span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
        <div class="row"><span>Kasir</span><span>{{ $sale->cashier?->nickname ?: $sale->cashier?->name ?? '-' }}</span></div>
        @if ($sale->customer_name)
            <div class="row"><span>Pembeli</span><span>{{ $sale->customer_name }}</span></div>
        @endif
        <div class="rule"></div>

        @foreach ($sale->groupedLines() as $group)
            <div class="row group"><span>{{ $group['label'] }}</span><span>{{ $rp($group['total']) }}</span></div>
            @foreach ($group['lines'] as $line)
                <div class="row line"><span>{{ $line->quantity }}x {{ $line->description }}</span><span>{{ $rp($line->subtotal()) }}</span></div>
            @endforeach
        @endforeach

        <div class="rule"></div>
        <div class="row total"><span>TOTAL</span><span>Rp {{ $rp($sale->total) }}</span></div>
        @if ($qrisSvg = $sale->qrisSvg())
            <div class="rule"></div>
            <div class="qris">
                <div>Scan QRIS untuk bayar</div>
                {!! $qrisSvg !!}
                <strong>Rp {{ $rp($sale->total) }}</strong>
                <div class="muted">Nominal terisi otomatis</div>
            </div>
        @else
            <div class="row"><span>{{ $sale->payment_method->label() }}</span><span>{{ $rp($sale->paid_amount) }}</span></div>
            <div class="row"><span>Kembali</span><span>{{ $rp($sale->change()) }}</span></div>
        @endif
        <div class="rule"></div>
        <div class="center muted">Terima kasih! Dirakit langsung di tempat.<br>Simpan struk ini sebagai bukti pembelian.</div>
    </div>

    <script>
        if (new URLSearchParams(location.search).has('cetak')) {
            window.addEventListener('load', () => window.print());
        }
    </script>
</body>
</html>
