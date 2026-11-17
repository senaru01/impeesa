@extends('layouts.member')

@section('title', $item->exists ? 'Ubah Item' : 'Tambah Item')

@php
    $rp = fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
    $category = old('category', $item->category?->value);
@endphp

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Toko &middot; Item</p>
            <h1 class="m-title">{{ $item->exists ? $item->label() : 'Tambah item' }}</h1>
            <p class="m-subtitle">Nama keycap dan base cap boleh dikosongkan, akan dibuat otomatis (mis. "Keycap A", "Base cap 5 huruf").</p>
        </div>
        <a href="{{ route('admin.store.items.index') }}" class="m-btn m-btn--ghost">Kembali</a>
    </div>

    @include('admin.store.nav')

    <div class="m-layout">
        <form method="POST" action="{{ $item->exists ? route('admin.store.items.update', $item) : route('admin.store.items.store') }}" class="m-card m-form" data-category-form>
            @csrf
            @if ($item->exists)
                @method('PUT')
            @endif
            @include('member.partials.errors')

            <div class="m-form__grid">
                <div class="m-field">
                    <label for="category">Kategori</label>
                    <select id="category" name="category" class="m-input" data-category-select required>
                        @foreach ($categories as $option)
                            <option value="{{ $option->value }}" @selected($category === $option->value)>{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="m-field" data-show-for="switch accessory">
                    <label for="name">Nama item</label>
                    <input id="name" name="name" class="m-input" maxlength="120" value="{{ old('name', $item->name) }}" placeholder="mis. Switch Outemu Blue, Ring gantungan">
                </div>
                <div class="m-field" data-show-for="keycap">
                    <label for="code">Huruf/angka keycap</label>
                    <input id="code" name="code" class="m-input" maxlength="4" value="{{ old('code', $item->code) }}" placeholder="A">
                    <span class="m-field__hint">Nama otomatis "Keycap A".</span>
                </div>
                <div class="m-field" data-show-for="base_cap">
                    <label for="size">Jumlah huruf base</label>
                    <input id="size" name="size" type="number" min="1" max="{{ \App\Models\StoreItem::MAX_BASE_SIZE }}" class="m-input" value="{{ old('size', $item->size) }}" placeholder="1-{{ \App\Models\StoreItem::MAX_BASE_SIZE }}">
                    <span class="m-field__hint">Nama otomatis "Base cap 5 huruf".</span>
                </div>
                <div class="m-field" data-show-for="keycap base_cap accessory">
                    <label for="color"><span data-show-for="keycap">Warna badan cap</span><span data-show-for="base_cap accessory">Warna</span></label>
                    <input id="color" name="color" class="m-input" list="store-colors" maxlength="40" value="{{ old('color', $item->color) }}" placeholder="mis. Putih">
                    <datalist id="store-colors">@foreach ($colors as $color)<option value="{{ $color }}">@endforeach</datalist>
                </div>
                <div class="m-field" data-show-for="keycap">
                    <label for="letter_color">Warna huruf</label>
                    <input id="letter_color" name="letter_color" class="m-input" list="store-colors" maxlength="40" value="{{ old('letter_color', $item->letter_color) }}" placeholder="mis. Hitam">
                    <span class="m-field__hint">Keycap 2 filament (AMS): {{ 100 - \App\Models\StoreItem::LETTER_FILAMENT_SHARE * 100 }}% gram badan, {{ \App\Models\StoreItem::LETTER_FILAMENT_SHARE * 100 }}% gram huruf.</span>
                </div>
                <div class="m-field" data-show-for="keycap base_cap accessory">
                    <label for="filament_grams">Filament per pcs (gram)</label>
                    <input id="filament_grams" name="filament_grams" type="number" min="0" step="any" class="m-input" value="{{ old('filament_grams', $item->filament_grams) }}" required>
                    <span class="m-field__hint"><span data-show-for="keycap">Total gram 1 pcs (badan + huruf). </span>Isi 0 bila aksesoris beli jadi.</span>
                </div>
                <div class="m-field" data-show-for="keycap base_cap accessory">
                    <label for="fixed_cost">Biaya tetap / pcs (Rp)</label>
                    <input id="fixed_cost" name="fixed_cost" type="number" min="0" step="1" class="m-input" value="{{ old('fixed_cost', $item->fixed_cost ?? 0) }}" required>
                    <span class="m-field__hint">Listrik + penyusutan printer per pcs.</span>
                </div>
                <div class="m-field">
                    <label for="purchase_price"><span data-show-for="switch">Harga beli per pcs (Rp)</span><span data-show-for="keycap base_cap accessory">Modal / HPP per pcs (Rp)</span></label>
                    <input id="purchase_price" name="purchase_price" type="number" min="0" step="1" class="m-input" value="{{ old('purchase_price', $item->purchase_price) }}" @unless ($item->exists) placeholder="kosongkan bila pakai roll" @endunless>
                    <span class="m-field__hint" data-show-for="keycap base_cap accessory">Otomatis dihitung ulang tiap produksi dari roll filament.</span>
                </div>
                @unless ($item->exists)
                    <div class="m-field m-form__full" data-show-for="keycap base_cap accessory">
                        <label for="filament_lot_id">Stok awal dicetak dari roll</label>
                        <select id="filament_lot_id" name="filament_lot_id" class="m-input">
                            <option value="">Tidak pakai roll (barang beli jadi / isi modal manual)</option>
                            @foreach ($lots as $lot)
                                <option value="{{ $lot->id }}" @selected((int) old('filament_lot_id') === $lot->id)>{{ $lot->optionLabel() }}</option>
                            @endforeach
                        </select>
                        <span class="m-field__hint" data-show-for="keycap">Roll warna badan cap.</span>
                    </div>
                    <div class="m-field m-form__full" data-show-for="keycap">
                        <label for="letter_filament_lot_id">Roll warna huruf</label>
                        <select id="letter_filament_lot_id" name="letter_filament_lot_id" class="m-input">
                            <option value="">Sama dengan roll badan</option>
                            @foreach ($lots as $lot)
                                <option value="{{ $lot->id }}" @selected((int) old('letter_filament_lot_id') === $lot->id)>{{ $lot->optionLabel() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                <div class="m-field">
                    <label for="selling_price">Harga jual (Rp)</label>
                    <input id="selling_price" name="selling_price" type="number" min="0" step="1" class="m-input" value="{{ old('selling_price', $item->selling_price) }}" required>
                </div>
                @unless ($item->exists)
                    <div class="m-field">
                        <label for="stock">Stok awal</label>
                        <input id="stock" name="stock" type="number" min="0" class="m-input" value="{{ old('stock', $item->stock) }}" required>
                        <span class="m-field__hint">Dicatat sebagai produksi (uang keluar = stok × harga beli).</span>
                    </div>
                @endunless
                <div class="m-field">
                    <label for="min_stock">Stok minimum</label>
                    <input id="min_stock" name="min_stock" type="number" min="0" class="m-input" value="{{ old('min_stock', $item->min_stock) }}" required>
                    <span class="m-field__hint">Muncul di daftar "perlu diproduksi" bila stok &le; angka ini.</span>
                </div>
            </div>
            <label class="m-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active))> Aktif (bisa dijual)</label>
            <div class="m-actions">
                <button type="submit" class="m-btn m-btn--primary">Simpan</button>
            </div>
        </form>

        @if ($item->exists)
            <div style="display: grid; gap: 20px">
                <form method="POST" action="{{ route('admin.store.items.restock', $item) }}" class="m-card m-form">
                    @csrf
                    <h2 class="m-card__title">Stok sekarang: {{ $item->stock }}</h2>
                    @include('member.partials.errors', ['bag' => 'restock'])
                    <div class="m-field">
                        <label for="type">Jenis</label>
                        <select id="type" name="type" class="m-input">
                            <option value="{{ \App\Enums\StockMovementType::Production->value }}">Produksi / beli (stok bertambah, uang keluar)</option>
                            <option value="{{ \App\Enums\StockMovementType::Adjustment->value }}" @selected(old('type') === \App\Enums\StockMovementType::Adjustment->value)>Koreksi stok (rusak, hilang, hitung ulang)</option>
                        </select>
                    </div>
                    @if ($item->filament_grams > 0)
                        <div class="m-field">
                            <label for="restock_lot">Dicetak dari roll</label>
                            <select id="restock_lot" name="filament_lot_id" class="m-input">
                                <option value="">Tanpa roll (isi biaya manual)</option>
                                @foreach ($lots as $lot)
                                    <option value="{{ $lot->id }}" @selected(old('filament_lot_id') ? (int) old('filament_lot_id') === $lot->id : $lot->id === $lots->firstWhere('color', $item->color)?->id)>{{ $lot->optionLabel() }} · modal {{ $rp($item->unitCostFrom($lot)) }}/pcs</option>
                                @endforeach
                            </select>
                            <span class="m-field__hint">{{ $rp($item->fixed_cost) }} biaya tetap + {{ number_format($item->filament_grams, 1, ',', '.') }} g × harga/gram roll. Gram dipotong dari roll terlama dulu (FIFO).</span>
                        </div>
                        @if ($item->hasLetterColor())
                            <div class="m-field">
                                <label for="restock_letter_lot">Roll huruf ({{ $item->letter_color }})</label>
                                <select id="restock_letter_lot" name="letter_filament_lot_id" class="m-input">
                                    <option value="">Sama dengan roll badan</option>
                                    @foreach ($lots as $lot)
                                        <option value="{{ $lot->id }}" @selected(old('letter_filament_lot_id') ? (int) old('letter_filament_lot_id') === $lot->id : $lot->id === $lots->firstWhere('color', $item->letter_color)?->id)>{{ $lot->optionLabel() }}</option>
                                    @endforeach
                                </select>
                                <span class="m-field__hint">{{ \App\Models\StoreItem::LETTER_FILAMENT_SHARE * 100 }}% gram ({{ number_format($item->filament_grams * \App\Models\StoreItem::LETTER_FILAMENT_SHARE, 2, ',', '.') }} g/pcs) diambil dari roll ini, modal ikut harga roll huruf.</span>
                            </div>
                        @endif
                    @endif
                    <div class="m-form__grid">
                        <div class="m-field">
                            <label for="restock_quantity">Jumlah</label>
                            <input id="restock_quantity" name="quantity" type="number" class="m-input" value="{{ old('quantity') }}" required>
                            <span class="m-field__hint">Koreksi boleh minus, mis. -2.</span>
                        </div>
                        <div class="m-field">
                            <label for="unit_cost">Biaya per pcs (tanpa roll)</label>
                            <input id="unit_cost" name="unit_cost" type="number" min="0" class="m-input" value="{{ old('unit_cost') }}" placeholder="{{ $item->purchase_price }}">
                        </div>
                    </div>
                    <div class="m-field">
                        <label for="restock_notes">Catatan</label>
                        <input id="restock_notes" name="notes" class="m-input" maxlength="255" value="{{ old('notes') }}" placeholder="mis. cetak batch 12 Nov">
                    </div>
                    <button type="submit" class="m-btn m-btn--primary">Simpan mutasi stok</button>
                </form>

                <section class="m-card">
                    <h2 class="m-card__title" style="margin-bottom: 14px">Riwayat stok</h2>
                    @if ($movements->isEmpty())
                        <p class="m-empty">Belum ada mutasi stok.</p>
                    @else
                        <div class="m-cart__lines">
                            @foreach ($movements as $movement)
                                <div class="m-row">
                                    <span>
                                        {{ $movement->type->label() }}
                                        @if ($movement->sale)
                                            <a href="{{ route('admin.store.sales.show', $movement->sale) }}" style="text-decoration: underline">{{ $movement->sale->number }}</a>
                                        @endif
                                        <span class="m-list__meta" style="display: block">{{ $movement->created_at->translatedFormat('d M Y H:i') }}{{ $movement->totalCost() ? ' · '.$rp($movement->totalCost()) : '' }}{{ $movement->notes ? ' · '.$movement->notes : '' }}</span>
                                    </span>
                                    <span class="m-money">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/store-pos.js') }}?v={{ filemtime(public_path('js/store-pos.js')) }}" defer></script>
@endpush
