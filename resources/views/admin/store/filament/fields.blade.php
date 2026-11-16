<div class="m-form__grid">
    <div class="m-field">
        <label for="material">Jenis</label>
        <input id="material" name="material" class="m-input" list="filament-materials" maxlength="20" value="{{ old('material', $lot->material) }}" required>
        <datalist id="filament-materials">@foreach ($materials as $material)<option value="{{ $material }}">@endforeach</datalist>
    </div>
    <div class="m-field">
        <label for="color">Warna</label>
        <input id="color" name="color" class="m-input" list="filament-colors" maxlength="40" value="{{ old('color', $lot->color) }}" placeholder="mis. Putih" required>
        <datalist id="filament-colors">@foreach ($colors as $color)<option value="{{ $color }}">@endforeach</datalist>
        <span class="m-field__hint">Samakan dengan nama warna keycap/base.</span>
    </div>
    <div class="m-field">
        <label for="brand">Merek</label>
        <input id="brand" name="brand" class="m-input" maxlength="60" value="{{ old('brand', $lot->brand) }}" placeholder="Opsional">
    </div>
    <div class="m-field">
        <label for="purchased_on">Tanggal beli</label>
        <input id="purchased_on" name="purchased_on" type="date" class="m-input" max="{{ now()->format('Y-m-d') }}" value="{{ old('purchased_on', $lot->purchased_on?->format('Y-m-d')) }}" required>
    </div>
    <div class="m-field">
        <label for="weight_grams">Berat per roll (gram)</label>
        <input id="weight_grams" name="weight_grams" type="number" min="1" class="m-input" value="{{ old('weight_grams', $lot->weight_grams) }}" required>
    </div>
    <div class="m-field">
        <label for="price">Harga per roll (Rp)</label>
        <input id="price" name="price" type="number" min="0" step="1" class="m-input" value="{{ old('price', $lot->price) }}" required>
    </div>
    @if ($lot->exists)
        <div class="m-field">
            <label for="remaining_grams">Sisa (gram)</label>
            <input id="remaining_grams" name="remaining_grams" type="number" min="0" step="any" class="m-input" value="{{ old('remaining_grams', $lot->remaining_grams) }}" required>
            <span class="m-field__hint">Koreksi bila ditimbang ulang (gagal cetak, sisa purge).</span>
        </div>
    @else
        <div class="m-field">
            <label for="quantity">Jumlah roll</label>
            <input id="quantity" name="quantity" type="number" min="1" max="100" class="m-input" value="{{ old('quantity', 1) }}" required>
        </div>
    @endif
    <div class="m-field m-form__full">
        <label for="lot_notes">Catatan</label>
        <input id="lot_notes" name="notes" class="m-input" maxlength="255" value="{{ old('notes', $lot->notes) }}" placeholder="mis. beli di marketplace, promo 11.11">
    </div>
</div>
