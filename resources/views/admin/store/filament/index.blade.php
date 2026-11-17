@extends('layouts.member')

@section('title', 'Filament')
@section('wrap', 'm-wrap--wide')

@php
    $rp = fn (int|float $value): string => 'Rp '.number_format($value, 0, ',', '.');
    $gram = fn (float $value): string => number_format($value, 0, ',', '.');
@endphp

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Toko</p>
            <h1 class="m-title">Stok filament</h1>
            <p class="m-subtitle">Roll dengan jenis &amp; warna sama masuk satu master. Produksi selalu memakai roll terlama dulu (FIFO), lalu lanjut ke roll berikutnya.</p>
        </div>
    </div>

    @include('admin.store.nav')

    <div class="m-grid m-stats" style="margin-bottom: 20px">
        <article class="m-card">
            <p class="m-health__label">Sisa filament</p>
            <p class="m-health__value">{{ $gram($totals['remaining']) }}<span>gram</span></p>
            <p class="m-health__meta">{{ $totals['rolls'] }} roll masih ada isinya</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Nilai sisa filament</p>
            <p class="m-health__value m-money">{{ $rp($totals['value']) }}</p>
            <p class="m-health__meta">sisa gram × harga per gram</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Total belanja filament</p>
            <p class="m-health__value m-money">{{ $rp($totals['spent']) }}</p>
            <p class="m-health__meta">semua roll tercatat</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Terbuang / NG</p>
            <p class="m-health__value m-money" @style(['color: var(--m-red)' => $totals['wastedValue'] > 0])>{{ $rp($totals['wastedValue']) }}</p>
            <p class="m-health__meta">{{ $gram($totals['wasted']) }} g tanpa hasil, modal tidak kembali</p>
        </article>
    </div>

    <div class="m-layout m-layout--store">
        <div style="display: grid; gap: 20px; min-width: 0">
            <div class="m-card">
                <div class="m-table-wrap">
                    <table class="m-table m-table--filament">
                        <thead><tr><th>Roll</th><th>Status</th><th>Beli</th><th>Harga</th><th>Per gram</th><th>Sisa</th><th></th></tr></thead>
                        @forelse ($groups as $group)
                            <tbody class="m-fgroup">
                                <tr class="m-fgroup__head">
                                    <td colspan="7">
                                        <div class="m-table__person">
                                            <span class="m-filament-swatch" data-swatch="{{ $group['color'] }}"></span>
                                            <div>
                                                <p class="m-list__title">{{ $group['label'] }}</p>
                                                <p class="m-list__meta">
                                                    @if ($group['rolls']->isEmpty())
                                                        Habis semua &middot; {{ $group['empty']->count() }} roll tercatat
                                                    @else
                                                        {{ $group['rolls']->count() }} roll &middot; sisa {{ $gram($group['remaining']) }} g &middot; nilai {{ $rp($group['value']) }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @foreach ($group['rolls']->concat($group['empty']) as $lot)
                                    @php($isEmpty = $lot->remaining_grams <= 0)
                                    <tr @class(['is-low' => ! $isEmpty && $lot->remainingPercent() <= 15, 'is-muted' => $isEmpty])>
                                        <td>
                                            <p class="m-list__title" style="font-weight: 500">{{ $lot->brand ?: 'Tanpa merek' }}</p>
                                            @if ($lot->notes)<p class="m-list__meta">{{ $lot->notes }}</p>@endif
                                        </td>
                                        <td>
                                            @if ($isEmpty)
                                                <span class="m-chip m-chip--grey">Habis</span>
                                            @elseif ($loop->first)
                                                <span class="m-chip m-chip--green">Dipakai</span>
                                            @else
                                                <span class="m-chip m-chip--grey">Cadangan {{ $loop->index }}</span>
                                            @endif
                                        </td>
                                        <td style="white-space: nowrap">{{ $lot->purchased_on->translatedFormat('d M Y') }}</td>
                                        <td class="m-money">{{ $rp($lot->price) }}<p class="m-list__meta">{{ $gram($lot->weight_grams) }} g</p></td>
                                        <td class="m-money">{{ 'Rp '.number_format($lot->pricePerGram(), 1, ',', '.') }}</td>
                                        <td style="min-width: 140px">
                                            <div class="m-meter"><span style="width: {{ $lot->remainingPercent() }}%"></span></div>
                                            <p class="m-list__meta">{{ $gram(max(0, $lot->remaining_grams)) }} g &middot; {{ $lot->remainingPercent() }}%</p>
                                        </td>
                                        <td>
                                            <div class="m-table__actions">
                                                <a href="{{ route('admin.store.filament.edit', $lot) }}" class="m-btn m-btn--ghost m-btn--sm">Ubah</a>
                                                @if ($lot->movements_count + $lot->letter_movements_count === 0)
                                                    <form method="POST" action="{{ route('admin.store.filament.destroy', $lot) }}" onsubmit="return confirm('Hapus catatan roll ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="m-btn m-btn--danger m-btn--sm">Hapus</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @empty
                            <tbody><tr><td colspan="7" class="m-empty">Belum ada filament. Catat pembelian roll pertama di formulir samping.</td></tr></tbody>
                        @endforelse
                    </table>
                </div>
            </div>

            <section class="m-card">
                <h2 class="m-card__title" style="margin-bottom: 14px">Filament terbuang / NG terakhir</h2>
                @if ($wastes->isEmpty())
                    <p class="m-empty">Belum ada filament yang dibuang.</p>
                @else
                    <div class="m-cart__lines">
                        @foreach ($wastes as $waste)
                            <div class="m-row">
                                <span>
                                    {{ $waste->lot->groupLabel() }}{{ $waste->lot->brand ? ' '.$waste->lot->brand : '' }} &middot; {{ $waste->reason->label() }}
                                    <span class="m-list__meta" style="display: block">{{ $waste->created_at->translatedFormat('d M Y H:i') }}{{ $waste->notes ? ' · '.$waste->notes : '' }}</span>
                                </span>
                                <span class="m-money">{{ $gram($waste->grams) }} g<span class="m-list__meta" style="display: block; color: var(--m-red)">-{{ $rp($waste->value) }}</span></span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="m-side">
            <form method="POST" action="{{ route('admin.store.filament.store') }}" class="m-card m-form">
                @csrf
                <h2 class="m-card__title">Catat beli filament</h2>
                <p class="m-field__hint" style="margin-top: -10px">Dihitung sebagai uang keluar di arus kas pada tanggal beli. Warna yang sudah ada otomatis masuk ke master yang sama sebagai roll cadangan.</p>
                @include('member.partials.errors')
                @include('admin.store.filament.fields', ['lot' => $newLot])
                <button type="submit" class="m-btn m-btn--primary">Simpan</button>
            </form>

            <form method="POST" action="{{ route('admin.store.filament.waste') }}" class="m-card m-form">
                @csrf
                <h2 class="m-card__title">Buang sisa / NG</h2>
                <p class="m-field__hint" style="margin-top: -10px">Sisa roll yang rusak atau dipakai cetakan gagal. Sisa roll berkurang, modal tidak kembali, dan tidak ada stok bertambah.</p>
                @include('member.partials.errors', ['bag' => 'waste'])
                <div class="m-field">
                    <label for="waste_lot">Roll</label>
                    <select id="waste_lot" name="filament_lot_id" class="m-input" required>
                        <option value="">Pilih roll</option>
                        @foreach ($groups as $group)
                            @continue($group['rolls']->isEmpty())
                            <optgroup label="{{ $group['label'] }}">
                                @foreach ($group['rolls'] as $lot)
                                    <option value="{{ $lot->id }}" @selected((int) old('filament_lot_id') === $lot->id)>{{ $loop->first ? 'Dipakai' : 'Cadangan '.$loop->index }} &middot; {{ $lot->brand ?: 'Tanpa merek' }} &middot; beli {{ $lot->purchased_on->translatedFormat('d M') }} &middot; sisa {{ $gram($lot->remaining_grams) }} g</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="m-form__grid">
                    <div class="m-field">
                        <label for="waste_reason">Alasan</label>
                        <select id="waste_reason" name="reason" class="m-input" required>
                            @foreach ($wasteReasons as $reason)
                                <option value="{{ $reason->value }}" @selected(old('reason') === $reason->value)>{{ $reason->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="m-field">
                        <label for="waste_grams">Gram</label>
                        <input id="waste_grams" name="grams" type="number" min="0" step="any" inputmode="decimal" class="m-input" value="{{ old('grams') }}" placeholder="semua sisa">
                    </div>
                </div>
                <span class="m-field__hint" style="margin-top: -8px">Kosongkan gram untuk membuang seluruh sisa roll.</span>
                <div class="m-field">
                    <label for="waste_notes">Catatan</label>
                    <input id="waste_notes" name="notes" class="m-input" maxlength="255" value="{{ old('notes') }}" placeholder="mis. filament getas, lembap">
                </div>
                <button type="submit" class="m-btn m-btn--danger" onclick="return confirm('Catat filament ini sebagai terbuang? Sisa roll akan berkurang.')">Catat terbuang</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/store-pos.js') }}?v={{ filemtime(public_path('js/store-pos.js')) }}" defer></script>
@endpush
