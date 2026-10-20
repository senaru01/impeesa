{{-- Form unggah foto. Parameter: $action, $title, $hint (opsional), $class (opsional). --}}
<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="m-card m-form {{ $class ?? '' }}">
    @csrf
    <h2 class="m-card__title">{{ $title }}</h2>
    @isset($hint)
        <p class="m-field__hint" style="margin-top: -8px">{{ $hint }}</p>
    @endisset
    @include('member.partials.errors')
    <div class="m-field">
        <label for="photos">Pilih foto</label>
        <input id="photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required class="m-input" data-photo-picker data-max="10">
        <span class="m-field__hint">Maks. 10 foto sekaligus, masing-masing 8 MB.</span>
    </div>
    <div class="m-field">
        <label for="caption">Keterangan</label>
        <input id="caption" name="caption" class="m-input" value="{{ old('caption') }}" placeholder="mis. Sunrise di Plawangan Sembalun">
    </div>
    <div class="m-field">
        <label for="taken_on">Tanggal foto</label>
        <input id="taken_on" name="taken_on" type="date" class="m-input" value="{{ old('taken_on') }}">
    </div>
    <button type="submit" class="m-btn m-btn--primary">Unggah</button>
</form>
