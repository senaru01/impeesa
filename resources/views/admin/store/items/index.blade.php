@extends('layouts.member')

@section('title', 'Item & Stok')

@php
    $rp = fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
    $gram = fn (float $value): string => number_format($value, $value == floor($value) ? 0 : 1, ',', '.');
@endphp

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Toko</p>
            <h1 class="m-title">Item &amp; stok</h1>
            <p class="m-subtitle">Keycap, base cap, switch, dan aksesoris beserta harga beli, harga jual, filament, dan stoknya.</p>
        </div>
        <a href="{{ route('admin.store.items.create') }}" class="m-btn m-btn--primary">Tambah item</a>
    </div>

    @include('admin.store.nav')

    <div class="m-grid" style="margin-bottom: 20px">
        <article class="m-card">
            <p class="m-health__label">Jumlah item</p>
            <p class="m-health__value">{{ number_format($totals->item_count, 0, ',', '.') }}</p>
            <p class="m-health__meta">{{ number_format($totals->units, 0, ',', '.') }} pcs di gudang</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Nilai stok (modal)</p>
            <p class="m-health__value m-money" style="font-size: 30px">{{ $rp((int) $totals->stock_value) }}</p>
            <p class="m-health__meta">stok × harga beli</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Potensi omzet</p>
            <p class="m-health__value m-money" style="font-size: 30px">{{ $rp((int) $totals->potential_revenue) }}</p>
            <p class="m-health__meta">stok × harga jual</p>
        </article>
        <article class="m-card">
            <p class="m-health__label">Stok menipis</p>
            <p class="m-health__value">{{ $lowStockCount }}<span>item</span></p>
            <p class="m-health__meta"><a href="{{ route('admin.store.items.index', ['menipis' => 1]) }}" class="m-chip m-chip--accent">Tampilkan</a> stok &le; minimum</p>
        </article>
    </div>

    <div class="m-layout">
        <div class="m-card">
            <form method="GET" class="m-actions" style="margin-bottom: 16px">
                <select name="kategori" class="m-input" style="max-width: 170px" aria-label="Kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->value }}" @selected($filters['category'] === $category)>{{ $category->label() }}</option>
                    @endforeach
                </select>
                <select name="warna" class="m-input" style="max-width: 160px" aria-label="Warna">
                    <option value="">Semua warna</option>
                    @foreach ($colors as $color)
                        <option value="{{ $color }}" @selected($filters['color'] === $color)>{{ $color }}</option>
                    @endforeach
                </select>
                <input type="search" name="q" value="{{ $filters['search'] }}" class="m-input" placeholder="Cari nama / huruf" style="max-width: 200px">
                <label class="m-check"><input type="checkbox" name="menipis" value="1" @checked($filters['lowStock'])> Stok menipis</label>
                <button type="submit" class="m-btn m-btn--ghost">Filter</button>
            </form>

            @include('member.partials.errors', ['bag' => 'restock'])
            @include('member.partials.errors', ['bag' => 'bulk'])

            <form method="POST" action="{{ route('admin.store.items.bulk') }}" id="bulk-form" class="m-bulkbar" data-bulk-form>
                @csrf
                <span class="m-bulkbar__count" data-bulk-count>0 dipilih</span>
                <select name="action" class="m-input" aria-label="Aksi massal" required>
                    <option value="reset_stock">Reset stok ke 0</option>
                    <option value="delete">Hapus item</option>
                </select>
                <input type="hidden" name="kategori" value="{{ $filters['category']?->value }}">
                <input type="hidden" name="warna" value="{{ $filters['color'] }}">
                <input type="hidden" name="q" value="{{ $filters['search'] }}">
                <input type="hidden" name="menipis" value="{{ $filters['lowStock'] ? 1 : 0 }}">
                <label class="m-check"><input type="checkbox" name="all_items" value="1" data-bulk-all data-bulk-all-label="{{ $isFiltered ? 'semua '.$items->total().' item hasil filter' : 'SEMUA '.$items->total().' item' }}"> {{ $isFiltered ? 'Semua hasil filter' : 'Semua item' }} ({{ $items->total() }})</label>
                <button type="submit" class="m-btn m-btn--danger m-btn--sm">Jalankan</button>
            </form>

            <div class="m-table-wrap m-table-wrap--sticky">
                <table class="m-table">
                    <thead>
                        <tr><th><input type="checkbox" data-bulk-toggle aria-label="Pilih semua di halaman ini"></th><th>Item</th><th>Stok</th><th>Modal</th><th>Harga jual</th><th>Margin</th><th>Filament</th><th>Produksi</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr @class(['is-low' => $item->isLowStock()])>
                                <td><input type="checkbox" name="ids[]" value="{{ $item->id }}" form="bulk-form" data-bulk-item aria-label="Pilih {{ $item->label() }}"></td>
                                <td>
                                    <p class="m-list__title">{{ $item->label() }}</p>
                                    <p class="m-list__meta">
                                        {{ $item->category->label() }}
                                        @unless ($item->is_active) &middot; <span class="m-chip m-chip--grey">Nonaktif</span> @endunless
                                    </p>
                                </td>
                                <td>
                                    <span @class(['m-chip', 'm-chip--red' => $item->stock <= 0, 'm-chip--accent' => $item->stock > 0 && $item->isLowStock(), 'm-chip--green' => ! $item->isLowStock()])>{{ $item->stock }}</span>
                                    <p class="m-list__meta">min {{ $item->min_stock }}</p>
                                </td>
                                <td class="m-money">
                                    {{ $rp($item->purchase_price) }}
                                    @if ($item->fixed_cost)
                                        <p class="m-list__meta">tetap {{ $rp($item->fixed_cost) }}</p>
                                    @endif
                                </td>
                                <td class="m-money">{{ $rp($item->selling_price) }}</td>
                                <td class="m-money">{{ $rp($item->margin()) }}</td>
                                <td style="white-space: nowrap">
                                    {{ $gram($item->filament_grams) }} g
                                    @if ($item->hasLetterColor() && $item->filament_grams > 0)
                                        @php($split = $item->filamentSplit(true))
                                        <p class="m-list__meta">{{ $gram(round($split['main'], 2)) }} g {{ $item->color }}</p>
                                        <p class="m-list__meta">{{ $gram(round($split['letter'], 2)) }} g {{ $item->letter_color }}</p>
                                    @elseif ($item->filament_grams <= 0 && in_array($item->category, [\App\Enums\StoreItemCategory::Keycap, \App\Enums\StoreItemCategory::BaseCap], true))
                                        <p class="m-list__meta" style="color: var(--m-red)">belum diisi</p>
                                    @endif
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.store.items.restock', $item) }}" class="m-quickstock">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ \App\Enums\StockMovementType::Production->value }}">
                                        @if ($item->filament_grams > 0)
                                            <select name="filament_lot_id" class="m-input m-quickstock__lot" aria-label="Filament untuk {{ $item->label() }}">
                                                <option value="">Tanpa roll</option>
                                                @foreach ($lots as $lot)
                                                    <option value="{{ $lot->id }}" @selected($lot->id === $lots->firstWhere('color', $item->color)?->id)>{{ $lot->label() }}</option>
                                                @endforeach
                                            </select>
                                            @if ($item->hasLetterColor())
                                                <select name="letter_filament_lot_id" class="m-input m-quickstock__lot" aria-label="Roll huruf untuk {{ $item->label() }}">
                                                    <option value="">Huruf: roll sama</option>
                                                    @foreach ($lots as $lot)
                                                        <option value="{{ $lot->id }}" @selected($lot->id === $lots->firstWhere('color', $item->letter_color)?->id)>Huruf: {{ $lot->label() }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        @endif
                                        <input type="number" name="quantity" min="1" max="100000" class="m-input" placeholder="+pcs" aria-label="Tambah stok {{ $item->label() }}" required>
                                        <button type="submit" class="m-btn m-btn--ghost m-btn--sm">Tambah</button>
                                    </form>
                                </td>
                                <td>
                                    <div class="m-table__actions">
                                        <a href="{{ route('admin.store.items.edit', $item) }}" class="m-btn m-btn--ghost m-btn--sm">Ubah</a>
                                        <form method="POST" action="{{ route('admin.store.items.destroy', $item) }}" onsubmit="return confirm('Hapus {{ $item->label() }}? Riwayat penjualan tetap tersimpan.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="m-btn m-btn--danger m-btn--sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="m-empty">Belum ada item. Buat set keycap/base cap sekaligus di formulir samping, atau tambah item satu per satu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="m-pagination">{{ $items->links('pagination::simple-bootstrap-5') }}</div>
        </div>

        <div style="display: grid; gap: 20px">
            <form method="POST" action="{{ route('admin.store.items.batch') }}" class="m-card m-form" data-category-form>
                @csrf
                <h2 class="m-card__title">Buat satu set sekaligus</h2>
                <p class="m-field__hint" style="margin-top: -10px">Satu warna keycap A-Z &amp; 0-9, atau base cap 1-10 huruf. Item yang sudah ada dilewati.</p>
                @include('member.partials.errors', ['bag' => 'batch'])

                <div class="m-form__grid">
                    <div class="m-field">
                        <label for="batch_category">Jenis</label>
                        <select id="batch_category" name="category" class="m-input" data-category-select>
                            <option value="{{ \App\Enums\StoreItemCategory::Keycap->value }}" @selected(old('category') !== \App\Enums\StoreItemCategory::BaseCap->value)>Keycap</option>
                            <option value="{{ \App\Enums\StoreItemCategory::BaseCap->value }}" @selected(old('category') === \App\Enums\StoreItemCategory::BaseCap->value)>Base cap</option>
                        </select>
                    </div>
                    <div class="m-field">
                        <label for="batch_color"><span data-show-for="keycap">Warna badan</span><span data-show-for="base_cap">Warna base</span></label>
                        <input id="batch_color" name="color" class="m-input" list="store-colors" maxlength="40" value="{{ old('color') }}" placeholder="mis. Putih" required>
                    </div>
                </div>
                <div class="m-field" data-show-for="keycap">
                    <label for="batch_letter_color">Warna huruf</label>
                    <input id="batch_letter_color" name="letter_color" class="m-input" list="store-colors" maxlength="40" value="{{ old('letter_color') }}" placeholder="mis. Hitam">
                    <span class="m-field__hint">Keycap 2 filament (AMS): {{ 100 - \App\Models\StoreItem::LETTER_FILAMENT_SHARE * 100 }}% gram badan, {{ \App\Models\StoreItem::LETTER_FILAMENT_SHARE * 100 }}% gram huruf.</span>
                </div>
                <div class="m-field" data-show-for="keycap">
                    <label for="batch_codes">Karakter keycap</label>
                    <input id="batch_codes" name="codes" class="m-input" maxlength="72" value="{{ old('codes', \App\Models\StoreItem::KEYCAP_CODES) }}">
                    <span class="m-field__hint">Hapus karakter yang tidak dibuat.</span>
                </div>
                <div class="m-form__grid" data-show-for="base_cap">
                    <div class="m-field">
                        <label for="size_from">Base dari (huruf)</label>
                        <input id="size_from" name="size_from" type="number" min="1" max="{{ \App\Models\StoreItem::MAX_BASE_SIZE }}" class="m-input" value="{{ old('size_from', 1) }}">
                    </div>
                    <div class="m-field">
                        <label for="size_to">Sampai (huruf)</label>
                        <input id="size_to" name="size_to" type="number" min="1" max="{{ \App\Models\StoreItem::MAX_BASE_SIZE }}" class="m-input" value="{{ old('size_to', \App\Models\StoreItem::MAX_BASE_SIZE) }}">
                    </div>
                </div>
                <div class="m-form__grid">
                    <div class="m-field">
                        <label for="batch_stock">Stok awal / item</label>
                        <input id="batch_stock" name="stock" type="number" min="0" class="m-input" value="{{ old('stock', 0) }}" required>
                    </div>
                    <div class="m-field">
                        <label for="batch_min_stock">Stok minimum</label>
                        <input id="batch_min_stock" name="min_stock" type="number" min="0" class="m-input" value="{{ old('min_stock', 3) }}" required>
                    </div>
                    <div class="m-field m-form__full">
                        <label for="batch_filament_lot_id">Dicetak dari roll filament</label>
                        <select id="batch_filament_lot_id" name="filament_lot_id" class="m-input">
                            <option value="">Tidak pakai roll (isi modal manual)</option>
                            @foreach ($lots as $lot)
                                <option value="{{ $lot->id }}" @selected((int) old('filament_lot_id') === $lot->id)>{{ $lot->label() }} · Rp{{ number_format($lot->pricePerGram(), 1, ',', '.') }}/g</option>
                            @endforeach
                        </select>
                        <span class="m-field__hint"><span data-show-for="keycap">Roll warna badan. </span>Modal = biaya tetap + gram × harga/gram roll, dan gram stok awal dipotong dari roll.</span>
                    </div>
                    <div class="m-field m-form__full" data-show-for="keycap">
                        <label for="batch_letter_filament_lot_id">Roll warna huruf</label>
                        <select id="batch_letter_filament_lot_id" name="letter_filament_lot_id" class="m-input">
                            <option value="">Sama dengan roll badan</option>
                            @foreach ($lots as $lot)
                                <option value="{{ $lot->id }}" @selected((int) old('letter_filament_lot_id') === $lot->id)>{{ $lot->label() }} · Rp{{ number_format($lot->pricePerGram(), 1, ',', '.') }}/g</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="m-field">
                        <label for="batch_fixed_cost">Biaya tetap / pcs (Rp)</label>
                        <input id="batch_fixed_cost" name="fixed_cost" type="number" min="0" step="1" class="m-input" value="{{ old('fixed_cost', 0) }}" required>
                        <span class="m-field__hint">Listrik + penyusutan printer.</span>
                    </div>
                    <div class="m-field">
                        <label for="batch_purchase_price">Modal manual (Rp)</label>
                        <input id="batch_purchase_price" name="purchase_price" type="number" min="0" step="1" class="m-input" value="{{ old('purchase_price') }}" placeholder="kosongkan bila pakai roll">
                    </div>
                    <div class="m-field">
                        <label for="batch_selling_price">Harga jual (Rp)</label>
                        <input id="batch_selling_price" name="selling_price" type="number" min="0" step="1" class="m-input" value="{{ old('selling_price') }}" required>
                    </div>
                    <div class="m-field">
                        <label for="batch_filament_grams">Filament per pcs (gram)</label>
                        <input id="batch_filament_grams" name="filament_grams" type="number" min="0" step="any" class="m-input" value="{{ old('filament_grams') }}" placeholder="mis. 3,5" required>
                        <span class="m-field__hint">Total gram 1 pcs (badan + huruf).</span>
                    </div>
                </div>
                <details class="m-details" data-show-for="base_cap" @if (old('selling_price_step') || old('purchase_price_step') || old('filament_grams_step')) open @endif>
                    <summary>Kenaikan harga per tambahan huruf</summary>
                    <div class="m-form__grid--3 m-form__grid" style="margin-top: 10px">
                        <div class="m-field">
                            <label for="purchase_price_step">Modal +Rp</label>
                            <input id="purchase_price_step" name="purchase_price_step" type="number" min="0" step="1" class="m-input" value="{{ old('purchase_price_step') }}">
                        </div>
                        <div class="m-field">
                            <label for="selling_price_step">Jual +Rp</label>
                            <input id="selling_price_step" name="selling_price_step" type="number" min="0" step="1" class="m-input" value="{{ old('selling_price_step') }}">
                        </div>
                        <div class="m-field">
                            <label for="filament_grams_step">+gram</label>
                            <input id="filament_grams_step" name="filament_grams_step" type="number" min="0" step="any" class="m-input" value="{{ old('filament_grams_step') }}">
                        </div>
                    </div>
                    <span class="m-field__hint">Contoh: base 1 huruf Rp5.000, +Rp1.000 per huruf → base 5 huruf Rp9.000. Kenaikan modal hanya dipakai bila tidak pakai roll.</span>
                </details>
                <datalist id="store-colors">@foreach ($colors as $color)<option value="{{ $color }}">@endforeach</datalist>
                <button type="submit" class="m-btn m-btn--primary">Buat set</button>
            </form>

            <section class="m-card">
                <h2 class="m-card__title" style="margin-bottom: 14px">Produksi &amp; koreksi terakhir</h2>
                @if ($movements->isEmpty())
                    <p class="m-empty">Belum ada catatan produksi.</p>
                @else
                    <div class="m-cart__lines">
                        @foreach ($movements as $movement)
                            <div class="m-row">
                                <span>
                                    {{ $movement->item->label() }}
                                    <span class="m-list__meta" style="display: block">{{ $movement->type->label() }} &middot; {{ $movement->created_at->translatedFormat('d M H:i') }}{{ $movement->notes ? ' · '.$movement->notes : '' }}</span>
                                </span>
                                <span class="m-money">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/store-pos.js') }}?v={{ filemtime(public_path('js/store-pos.js')) }}" defer></script>
@endpush
