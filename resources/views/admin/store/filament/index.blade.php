@extends('layouts.member')

@section('title', 'Filament')

@php
    $rp = fn (int|float $value): string => 'Rp '.number_format($value, 0, ',', '.');
    $gram = fn (float $value): string => number_format($value, 0, ',', '.');
@endphp

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Toko</p>
            <h1 class="m-title">Stok filament</h1>
            <p class="m-subtitle">Tiap roll punya harga per gram sendiri. Modal keycap dan base dihitung dari roll yang dipakai mencetak.</p>
        </div>
    </div>

    @include('admin.store.nav')

    <div class="m-grid m-grid--3" style="margin-bottom: 20px">
        <article class="m-card">
            <p class="m-health__label">Sisa filament</p>
            <p class="m-health__value">{{ $gram($totals['remaining']) }}<span>gram</span></p>
            <p class="m-health__meta">{{ $lots->where('remaining_grams', '>', 0)->count() }} roll masih ada isinya</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Nilai sisa filament</p>
            <p class="m-health__value m-money" style="font-size: 30px">{{ $rp($totals['value']) }}</p>
            <p class="m-health__meta">sisa gram × harga per gram</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Total belanja filament</p>
            <p class="m-health__value m-money" style="font-size: 30px">{{ $rp($totals['spent']) }}</p>
            <p class="m-health__meta">semua roll tercatat</p>
        </article>
    </div>

    <div class="m-layout">
        <div class="m-card">
            <div class="m-table-wrap">
                <table class="m-table">
                    <thead><tr><th>Filament</th><th>Beli</th><th>Harga</th><th>Per gram</th><th>Sisa</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($lots as $lot)
                            <tr @class(['is-low' => $lot->remainingPercent() <= 15])>
                                <td>
                                    <div class="m-table__person">
                                        <span class="m-filament-swatch" data-swatch="{{ $lot->color }}"></span>
                                        <div>
                                            <p class="m-list__title">{{ $lot->material }} {{ $lot->color }}</p>
                                            <p class="m-list__meta">{{ $lot->brand ?: 'Tanpa merek' }}{{ $lot->notes ? ' · '.$lot->notes : '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td style="white-space: nowrap">{{ $lot->purchased_on->translatedFormat('d M Y') }}</td>
                                <td class="m-money">{{ $rp($lot->price) }}<p class="m-list__meta">{{ $gram($lot->weight_grams) }} g</p></td>
                                <td class="m-money">{{ 'Rp '.number_format($lot->pricePerGram(), 1, ',', '.') }}</td>
                                <td style="min-width: 140px">
                                    <div class="m-meter"><span style="width: {{ $lot->remainingPercent() }}%"></span></div>
                                    <p class="m-list__meta">{{ $gram($lot->remaining_grams) }} g &middot; {{ $lot->remainingPercent() }}%</p>
                                </td>
                                <td>
                                    <div class="m-table__actions">
                                        <a href="{{ route('admin.store.filament.edit', $lot) }}" class="m-btn m-btn--ghost m-btn--sm">Ubah</a>
                                        @if ($lot->movements_count + $lot->letter_movements_count === 0)
                                            <form method="POST" action="{{ route('admin.store.filament.destroy', $lot) }}" onsubmit="return confirm('Hapus filament ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="m-btn m-btn--danger m-btn--sm">Hapus</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="m-empty">Belum ada filament. Catat pembelian roll pertama di formulir samping.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.store.filament.store') }}" class="m-card m-form">
            @csrf
            <h2 class="m-card__title">Catat beli filament</h2>
            <p class="m-field__hint" style="margin-top: -10px">Dihitung sebagai uang keluar di arus kas pada tanggal beli.</p>
            @include('member.partials.errors')
            @include('admin.store.filament.fields', ['lot' => $newLot])
            <button type="submit" class="m-btn m-btn--primary">Simpan</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/store-pos.js') }}?v={{ filemtime(public_path('js/store-pos.js')) }}" defer></script>
@endpush
