@extends('layouts.member')

@section('title', 'Kasir Toko')
@section('wrap', 'm-wrap--wide')

@php
    $rp = fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
@endphp

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Toko</p>
            <h1 class="m-title">Kasir souvenir</h1>
            <p class="m-subtitle">Rakit gantungan kunci clicky, hitung harga otomatis, cetak struk, dan stok langsung berkurang.</p>
        </div>
        <div class="m-actions">
            <span class="m-chip m-chip--grey">Hari ini {{ $today['count'] }} transaksi</span>
            <span class="m-chip m-chip--green">{{ $rp($today['total']) }}</span>
        </div>
    </div>

    @include('admin.store.nav')

    <div class="m-layout m-layout--store">
        <div style="display: grid; gap: 20px">
            {{-- ============ RAKIT GANTUNGAN KUNCI ============ --}}
            <form method="POST" action="{{ route('admin.store.cart.keychain') }}" class="m-card m-form" data-keychain-form data-catalog='@json($catalog)'>
                @csrf
                <div class="m-showcase">
                    <div class="k3d" data-preview aria-hidden="true"><span class="m-keychain__empty">Pratinjau 3D muncul saat tulisan diisi.</span></div>
                    <div class="m-showcase__price" aria-live="polite">
                        <span data-big-label>Harga jual</span>
                        <strong class="m-money" data-big-total>Rp 0</strong>
                    </div>
                </div>

                @include('member.partials.errors', ['bag' => 'keychain'])

                @if ($keycapColors === [] || $baseColors === [] || $switches->isEmpty())
                    <p class="m-warn">Lengkapi dulu daftar item: minimal satu warna keycap, satu warna base cap, dan satu switch. <a href="{{ route('admin.store.items.index') }}" style="text-decoration: underline">Buka Item &amp; stok</a></p>
                @endif

                <div class="m-field">
                    <label for="text">Tulisan (maks. {{ \App\Models\StoreItem::MAX_BASE_SIZE }} huruf/angka)</label>
                    <input id="text" name="text" class="m-input m-keychain-input" maxlength="{{ \App\Models\StoreItem::MAX_BASE_SIZE }}" autocomplete="off" value="{{ old('text') }}" placeholder="FATAH" required>
                </div>

                <div class="m-form__grid">
                    <div class="m-field">
                        <label for="keycap_color">Warna keycap (cap, huruf)</label>
                        <select id="keycap_color" name="keycap_color" class="m-input" required>
                            @foreach ($keycapColors as $variant => $variantLabel)
                                <option value="{{ $variant }}" @selected(old('keycap_color') === $variant)>{{ $variantLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="m-field">
                        <label for="base_color">Warna base</label>
                        <select id="base_color" name="base_color" class="m-input" required>
                            @foreach ($baseColors as $color)
                                <option value="{{ $color }}" @selected(old('base_color') === $color)>{{ $color }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <details class="m-details" @if (old('letter_colors') && array_filter(old('letter_colors'))) open @endif>
                    <summary>Warna keycap beda-beda per huruf (opsional)</summary>
                    <p class="m-field__hint" style="margin-top: 8px">Mis. pesanan "FATAH" dengan F merah, A putih, dst. Tiap huruf boleh pilih kombinasi cap &amp; huruf sendiri.</p>
                    <div class="m-letter-colors" data-letter-colors data-old='@json(old('letter_colors', []))'></div>
                </details>

                <div class="m-form__grid--3 m-form__grid">
                    <div class="m-field">
                        <label for="switch_item_id">Switch</label>
                        <select id="switch_item_id" name="switch_item_id" class="m-input" required>
                            @foreach ($switches as $switch)
                                <option value="{{ $switch->id }}" @selected((int) old('switch_item_id') === $switch->id)>{{ $switch->label() }} ({{ $rp($switch->selling_price) }}/pcs · stok {{ $switch->stock }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="m-field">
                        <label for="accessory_item_id">Aksesoris</label>
                        <select id="accessory_item_id" name="accessory_item_id" class="m-input">
                            <option value="">Tanpa aksesoris</option>
                            @foreach ($accessories as $accessory)
                                <option value="{{ $accessory->id }}" data-color="{{ $accessory->color }}" @selected(old('accessory_item_id') !== null ? (int) old('accessory_item_id') === $accessory->id : $accessory->id === $defaultAccessoryId)>{{ $accessory->label() }} ({{ $accessory->selling_price > 0 ? $rp($accessory->selling_price) : 'gratis' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="m-field">
                        <label for="quantity">Jumlah</label>
                        <input id="quantity" name="quantity" type="number" min="1" max="50" class="m-input" value="{{ old('quantity', 1) }}" required>
                    </div>
                </div>

                <div class="m-estimate" data-estimate aria-live="polite">
                    <p class="m-row--muted">Rincian harga muncul saat tulisan diisi.</p>
                </div>

                <button type="submit" class="m-btn m-btn--primary">Masukkan ke keranjang</button>
            </form>

            {{-- ============ ITEM SATUAN ============ --}}
            <form method="POST" action="{{ route('admin.store.cart.item') }}" class="m-card m-form">
                @csrf
                <h2 class="m-card__title">Jual item satuan</h2>
                <p class="m-field__hint" style="margin-top: -10px">Mis. keycap cadangan, switch, atau aksesoris tanpa dirakit.</p>
                @include('member.partials.errors', ['bag' => 'cartItem'])
                <div class="m-form__grid">
                    <div class="m-field">
                        <label for="store_item_id">Item</label>
                        <select id="store_item_id" name="store_item_id" class="m-input" required>
                            @foreach ($items->groupBy(fn ($item) => $item->category->label()) as $categoryLabel => $categoryItems)
                                <optgroup label="{{ $categoryLabel }}">
                                    @foreach ($categoryItems as $item)
                                        <option value="{{ $item->id }}" @selected((int) old('store_item_id') === $item->id)>{{ $item->label() }} — {{ $rp($item->selling_price) }} (stok {{ $item->stock }})</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="m-field">
                        <label for="item_quantity">Jumlah</label>
                        <input id="item_quantity" name="quantity" type="number" min="1" max="500" class="m-input" value="1" required>
                    </div>
                </div>
                <button type="submit" class="m-btn m-btn--ghost">Tambah ke keranjang</button>
            </form>
        </div>

        {{-- ============ KERANJANG & CHECKOUT ============ --}}
        <div class="m-sticky m-side">
            <section class="m-card">
                <div class="m-card__head">
                    <h2 class="m-card__title">Keranjang</h2>
                    @if ($cart['groups'] !== [])
                        <form method="POST" action="{{ route('admin.store.cart.clear') }}" onsubmit="return confirm('Kosongkan keranjang?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="m-btn m-btn--danger m-btn--sm">Kosongkan</button>
                        </form>
                    @endif
                </div>

                @if ($cart['groups'] === [])
                    <p class="m-empty">Keranjang kosong. Rakit gantungan kunci di samping.</p>
                @else
                    <div class="m-cart">
                        @foreach ($cart['groups'] as $group)
                            <div class="m-cart__group">
                                <div class="m-cart__head">
                                    <span>{{ $group['label'] }}</span>
                                    <span class="m-money">{{ $rp($group['total']) }}</span>
                                </div>
                                <div class="m-cart__lines">
                                    @foreach ($group['lines'] as $line)
                                        <div class="m-row m-row--muted">
                                            <span>{{ $line['quantity'] }} × {{ $line['item']->label() }} @ {{ $rp($line['item']->selling_price) }}</span>
                                            <span class="m-money">{{ $rp($line['subtotal']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <form method="POST" action="{{ route('admin.store.cart.remove', $group['index']) }}" style="margin-top: 8px">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="m-btn m-btn--danger m-btn--sm">Hapus</button>
                                </form>
                            </div>
                        @endforeach
                    </div>

                    @if ($cart['shortages'] !== [])
                        <div class="m-warn" role="alert" style="margin-top: 14px">
                            <strong>Stok tidak cukup:</strong>
                            <ul>@foreach ($cart['shortages'] as $shortage)<li>{{ $shortage }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <div class="m-sum" style="margin-top: 16px">
                        <div class="m-row"><span>Total</span><span class="m-sum__total m-money">{{ $rp($cart['total']) }}</span></div>
                    </div>
                @endif
            </section>

            @if ($cart['groups'] !== [])
                <form method="POST" action="{{ route('admin.store.sales.store') }}" class="m-card m-form" data-checkout data-total="{{ $cart['total'] }}">
                    @csrf
                    <h2 class="m-card__title">Pembayaran</h2>
                    @include('member.partials.errors', ['bag' => 'checkout'])
                    <div class="m-field">
                        <label for="customer_name">Nama pembeli</label>
                        <input id="customer_name" name="customer_name" class="m-input" maxlength="100" value="{{ old('customer_name') }}" placeholder="Opsional">
                    </div>
                    <div class="m-form__grid">
                        <div class="m-field">
                            <label for="payment_method">Metode bayar</label>
                            <select id="payment_method" name="payment_method" class="m-input" required>
                                @foreach ($paymentMethods as $method)
                                    <option value="{{ $method->value }}" @selected(old('payment_method') === $method->value)>{{ $method->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="m-field">
                            <label for="paid_amount">Uang dibayar</label>
                            <input id="paid_amount" name="paid_amount" type="number" min="0" step="1" inputmode="numeric" class="m-input" value="{{ old('paid_amount') }}" placeholder="{{ $cart['total'] }}">
                        </div>
                    </div>
                    <p class="m-row"><span>Kembalian</span><strong class="m-money" data-change>Rp 0</strong></p>
                    <div class="m-field">
                        <label for="sale_notes">Catatan</label>
                        <input id="sale_notes" name="notes" class="m-input" maxlength="255" value="{{ old('notes') }}" placeholder="mis. bazar CFD">
                    </div>
                    <button type="submit" class="m-btn m-btn--primary m-btn--block" @disabled($cart['shortages'] !== [])>Bayar &amp; simpan transaksi</button>
                    <span class="m-field__hint">Kosongkan "uang dibayar" bila uang pas / non-tunai.</span>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/store-pos.js') }}?v={{ filemtime(public_path('js/store-pos.js')) }}" defer></script>
@endpush
