@extends('layouts.member')

@section('title', 'Penjualan & Arus Kas')
@section('wrap', 'm-wrap--wide')

@php
    $rp = fn (int $value): string => ($value < 0 ? '-' : '').'Rp '.number_format(abs($value), 0, ',', '.');
    $gram = fn (float $value): string => number_format($value, $value == floor($value) ? 0 : 1, ',', '.');
@endphp

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Toko</p>
            <h1 class="m-title">Penjualan &amp; arus kas</h1>
            <p class="m-subtitle">{{ $month->translatedFormat('F Y') }} &middot; uang masuk dari penjualan, uang keluar untuk produksi/beli stok.</p>
        </div>
        <form method="GET" class="m-actions">
            <input type="month" name="bulan" value="{{ $month->format('Y-m') }}" class="m-input" aria-label="Bulan">
            <button type="submit" class="m-btn m-btn--ghost">Tampilkan</button>
        </form>
    </div>

    @include('admin.store.nav')

    <div class="m-grid m-stats" style="margin-bottom: 20px">
        <article class="m-card">
            <p class="m-health__label">Omzet (uang masuk)</p>
            <p class="m-health__value m-money">{{ $rp($summary['revenue']) }}</p>
            <p class="m-health__meta">{{ $summary['count'] }} transaksi</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Laba kotor</p>
            <p class="m-health__value m-money">{{ $rp($summary['profit']) }}</p>
            <p class="m-health__meta">modal barang terjual {{ $rp($summary['cost']) }}</p>
            @if ($summary['wastedValue'] > 0)
                <p class="m-health__meta" style="color: var(--m-red)">filament terbuang/NG {{ $gram($summary['wastedGrams']) }} g &middot; -{{ $rp($summary['wastedValue']) }}</p>
            @endif
        </article>
        <article class="m-card">
            <p class="m-health__label">Belanja (uang keluar)</p>
            <p class="m-health__value m-money">{{ $rp($summary['cashOut']) }}</p>
            <p class="m-health__meta">filament {{ $rp($summary['filamentPurchases']) }} &middot; barang jadi {{ $rp($summary['goodsPurchases']) }}</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Arus kas bersih</p>
            <p class="m-health__value m-money" style="color: {{ $summary['netCash'] < 0 ? 'var(--m-red)' : 'inherit' }}">{{ $rp($summary['netCash']) }}</p>
            <p class="m-health__meta">filament dicetak {{ $gram($summary['filamentProduced']) }} g &middot; terjual {{ $gram($summary['filamentSold']) }} g</p>
        </article>
    </div>

    <div class="m-layout m-layout--store">
        <div class="m-card">
            <h2 class="m-card__title" style="margin-bottom: 14px">Transaksi</h2>
            <div class="m-table-wrap">
                <table class="m-table">
                    <thead><tr><th>No. struk</th><th>Waktu</th><th>Pembeli</th><th>Bayar</th><th>Total</th><th>Laba</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr>
                                <td class="m-list__title" style="white-space: nowrap">{{ $sale->number }}</td>
                                <td style="white-space: nowrap">{{ $sale->created_at->translatedFormat('d M H:i') }}</td>
                                <td>{{ $sale->customer_name ?: '–' }}</td>
                                <td><span class="m-chip m-chip--grey">{{ $sale->payment_method->label() }}</span></td>
                                <td class="m-money">{{ $rp($sale->total) }}</td>
                                <td class="m-money">{{ $rp($sale->profit()) }}</td>
                                <td>
                                    <div class="m-table__actions">
                                        <a href="{{ route('admin.store.sales.show', $sale) }}" class="m-btn m-btn--ghost m-btn--sm">Detail</a>
                                        <a href="{{ route('admin.store.sales.receipt', $sale) }}" target="_blank" class="m-btn m-btn--ghost m-btn--sm">Struk</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="m-empty">Belum ada penjualan di bulan ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="m-pagination">{{ $sales->links('pagination::simple-bootstrap-5') }}</div>
        </div>

        <div class="m-side">
            <section class="m-card">
                <div class="m-card__head">
                    <h2 class="m-card__title">Perlu diproduksi</h2>
                    <a href="{{ route('admin.store.items.index', ['menipis' => 1]) }}" class="m-chip m-chip--accent">Produksi</a>
                </div>
                @if ($lowStockItems->isEmpty())
                    <p class="m-empty">Semua stok masih di atas minimum.</p>
                @else
                    <div class="m-cart__lines">
                        @foreach ($lowStockItems as $item)
                            <div class="m-row">
                                <span>{{ $item->label() }}<span class="m-list__meta" style="display: block">min {{ $item->min_stock }}</span></span>
                                <span @class(['m-chip', 'm-chip--red' => $item->stock <= 0, 'm-chip--accent' => $item->stock > 0])>sisa {{ $item->stock }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="m-card">
                <h2 class="m-card__title" style="margin-bottom: 14px">Beli filament</h2>
                @if ($filamentLots->isEmpty())
                    <p class="m-empty">Belum ada pembelian filament di bulan ini.</p>
                @else
                    <div class="m-cart__lines">
                        @foreach ($filamentLots as $lot)
                            <div class="m-row">
                                <span>
                                    {{ $lot->material }} {{ $lot->color }} {{ number_format($lot->weight_grams, 0, ',', '.') }} g
                                    <span class="m-list__meta" style="display: block">{{ $lot->purchased_on->translatedFormat('d M') }}{{ $lot->brand ? ' · '.$lot->brand : '' }}</span>
                                </span>
                                <span class="m-money">{{ $rp($lot->price) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="m-card">
                <h2 class="m-card__title" style="margin-bottom: 14px">Produksi / beli barang jadi</h2>
                @if ($productionMovements->isEmpty())
                    <p class="m-empty">Belum ada produksi di bulan ini.</p>
                @else
                    <div class="m-cart__lines">
                        @foreach ($productionMovements as $movement)
                            <div class="m-row">
                                <span>
                                    +{{ $movement->quantity }} {{ $movement->item?->label() ?? 'Item terhapus' }}
                                    <span class="m-list__meta" style="display: block">
                                        {{ $movement->created_at->translatedFormat('d M H:i') }}
                                        @if ($movement->filamentLot)
                                            &middot; dicetak {{ $gram($movement->filament_grams) }} g dari {{ $movement->filamentLot->material }} {{ $movement->filamentLot->color }}{{ $movement->letterFilamentLot ? ' + '.$gram($movement->letter_filament_grams).' g huruf '.$movement->letterFilamentLot->color : '' }}, modal {{ $rp($movement->totalCost()) }}
                                        @endif
                                        {{ $movement->notes ? ' · '.$movement->notes : '' }}
                                    </span>
                                </span>
                                <span class="m-money">{{ $movement->cashOut() ? $rp($movement->cashOut()) : '–' }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="m-note">Barang cetak dari lot filament tidak dihitung uang keluar lagi, karena sudah dihitung saat filament dibeli.</p>
                @endif
            </section>
        </div>
    </div>
@endsection
