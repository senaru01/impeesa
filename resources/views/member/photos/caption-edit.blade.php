{{-- Ubah keterangan foto Galeri Bersama (khusus super admin). Parameter: $photo. --}}
@php($captionFailed = $errors->caption->any() && (int) old('caption_photo') === $photo->id)
<details class="m-caption-edit" @if ($captionFailed) open @endif>
    <summary title="Ubah keterangan">
        <span class="m-photo__caption">{{ $photo->caption ?: 'Tanpa keterangan' }}</span>
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 20h4L19 9l-4-4L4 16v4zM14 6l4 4"/></svg>
        <span class="m-sr">Ubah keterangan</span>
    </summary>
    <form method="POST" action="{{ route('photos.caption', $photo) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="caption_photo" value="{{ $photo->id }}">
        <input name="caption" class="m-input" value="{{ $captionFailed ? old('caption') : $photo->caption }}" maxlength="150" placeholder="mis. Pos 2 Ranu Kumbolo" aria-label="Keterangan foto" autocomplete="off">
        <button type="submit" class="m-btn m-btn--primary m-btn--sm">Simpan</button>
    </form>
    @if ($captionFailed)
        <span class="m-field__error">{{ $errors->caption->first('caption') }}</span>
    @endif
</details>
