@extends('layouts.member')

@section('title', 'Transaksi '.$sale->number)

@php
    $rp = fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
    $gram = fn (float $value): string => number_format($value, $value == floor($value) ? 0 : 1, ',', '.');
@endphp

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Toko &middot; Penjualan</p>
            <h1 class="m-title">{{ $sale->number }}</h1>
            <p class="m-subtitle">{{ $sale->created_at->translatedFormat('l, d F Y H:i') }} &middot; kasir {{ $sale->cashier?->name ?? '–' }}{{ $sale->customer_name ? ' · pembeli '.$sale->customer_name : '' }}</p>
        </div>
        <div class="m-actions">
            <a href="{{ route('admin.store.sales.receipt', ['sale' => $sale, 'cetak' => 1]) }}" target="_blank" class="m-btn m-btn--primary">Cetak struk</a>
            <a href="{{ route('admin.store.pos') }}" class="m-btn m-btn--ghost">Transaksi baru</a>
        </div>
    </div>

    @include('admin.store.nav')

    <div class="m-layout">
        <div class="m-card">
            <div class="m-table-wrap">
                <table class="m-table">
                    <thead><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
                    <tbody>
                        @foreach ($sale->groupedLines() as $group)
                            <tr>
                                <td colspan="3" class="m-list__title">{{ $group['label'] }}</td>
                                <td class="m-money m-list__title">{{ $rp($group['total']) }}</td>
                            </tr>
                            @foreach ($group['lines'] as $line)
                                <tr>
                                    <td style="padding-left: 28px; color: var(--m-muted)">{{ $line->description }}</td>
                                    <td>{{ $line->quantity }}</td>
                                    <td class="m-money">{{ $rp($line->unit_price) }}</td>
                                    <td class="m-money">{{ $rp($line->subtotal()) }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <section class="m-card">
            @if ($qrisSvg = $sale->qrisSvg())
                <div class="m-qris">
                    <p class="m-qris__label">Scan QRIS untuk bayar</p>
                    {!! $qrisSvg !!}
                    <p class="m-qris__amount m-money">{{ $rp($sale->total) }}</p>
                    <p class="m-field__hint">Nominal sudah terisi otomatis di aplikasi pembeli.</p>
                </div>
            @endif
            <div class="m-sum">
                <div class="m-row"><span>Total</span><span class="m-sum__total m-money">{{ $rp($sale->total) }}</span></div>
                <div class="m-row m-row--muted"><span>Dibayar ({{ $sale->payment_method->label() }})</span><span class="m-money">{{ $rp($sale->paid_amount) }}</span></div>
                <div class="m-row m-row--muted"><span>Kembalian</span><span class="m-money">{{ $rp($sale->change()) }}</span></div>
            </div>
            <details class="m-details" style="margin-top: 16px">
                <summary>Lihat modal &amp; laba</summary>
                <div class="m-sum" style="margin-top: 10px">
                    <div class="m-row m-row--muted"><span>Modal</span><span class="m-money">{{ $rp($sale->total_cost) }}</span></div>
                    <div class="m-row"><span>Laba kotor</span><strong class="m-money">{{ $rp($sale->profit()) }}</strong></div>
                    <div class="m-row m-row--muted"><span>Filament</span><span>{{ $gram($sale->filament_grams) }} g</span></div>
                    @foreach ($sale->lines as $line)
                        <div class="m-row m-row--muted"><span>{{ $line->quantity }} × {{ $line->description }}</span><span class="m-money">{{ $rp($line->quantity * $line->unit_cost) }}</span></div>
                    @endforeach
                </div>
            </details>
            @if ($sale->notes)
                <p class="m-note">{{ $sale->notes }}</p>
            @endif
        </section>
    </div>
@endsection
