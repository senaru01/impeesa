@extends('layouts.member')

@section('title', $story->exists ? 'Ubah Cerita' : 'Tulis Cerita')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb"><a href="{{ route('stories.index') }}">Cerita</a> &middot; {{ $story->exists ? 'Ubah' : 'Baru' }}</p>
            <h1 class="m-title">{{ $story->exists ? 'Ubah cerita' : 'Tulis cerita' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $story->exists ? route('stories.update', $story) : route('stories.store') }}" enctype="multipart/form-data" class="m-card m-form" style="max-width: 820px">
        @csrf
        @if ($story->exists)
            @method('PUT')
        @endif
        @include('member.partials.errors')

        <div class="m-field">
            <label for="title">Judul</label>
            <input id="title" name="title" class="m-input" value="{{ old('title', $story->title) }}" required maxlength="150" placeholder="mis. Hujan es di Plawangan Sembalun">
        </div>
        <div class="m-form__grid">
            <div class="m-field">
                <label for="mountain_name">Gunung</label>
                <input id="mountain_name" name="mountain_name" class="m-input" list="story-mountains" value="{{ old('mountain_name', $story->mountain_name) }}">
                <datalist id="story-mountains">
                    @foreach (collect(config('mountains.destinations'))->concat(config('mountains.others'))->sortBy('name') as $mountain)
                        <option value="{{ $mountain['name'] }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="m-field">
                <label for="climbed_on">Tanggal pendakian</label>
                <input id="climbed_on" name="climbed_on" type="date" class="m-input" value="{{ old('climbed_on', $story->climbed_on?->format('Y-m-d')) }}">
            </div>
        </div>
        <div class="m-field">
            <label for="body">Cerita</label>
            <textarea id="body" name="body" class="m-input" rows="12" required minlength="50" maxlength="5000">{{ old('body', $story->body) }}</textarea>
            <span class="m-field__hint">50 sampai 5.000 karakter. Pisahkan paragraf dengan baris kosong.</span>
        </div>
        <div class="m-field">
            <label for="cover">Foto sampul</label>
            <input id="cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" class="m-input">
            <span class="m-field__hint">Opsional. Foto sampul ikut tampil publik jika cerita dipilih untuk halaman utama.</span>
        </div>

        <fieldset class="m-field m-visibility">
            <legend class="m-field__label">Siapa yang bisa membaca?</legend>
            @php($isPublic = (bool) old('is_public', $story->is_public ?? false))
            <label @class(['m-visibility__option', 'is-checked' => ! $isPublic])>
                <input type="radio" name="is_public" value="0" @checked(! $isPublic)>
                <span>
                    <strong>Privat</strong>
                    <span>Hanya kamu dan super admin.</span>
                </span>
            </label>
            <label @class(['m-visibility__option', 'is-checked' => $isPublic])>
                <input type="radio" name="is_public" value="1" @checked($isPublic)>
                <span>
                    <strong>Publik</strong>
                    <span>Semua member bisa membaca di Cerita Bersama. Super admin bisa memilihnya untuk tampil di halaman utama web.</span>
                </span>
            </label>
            @if ($story->isFeatured())
                <span class="m-field__hint">Cerita ini sedang tampil di halaman utama. Jika dijadikan privat, cerita otomatis turun dari halaman utama.</span>
            @endif
        </fieldset>

        <div class="m-actions">
            <button type="submit" class="m-btn m-btn--primary">Simpan cerita</button>
            <a href="{{ route('stories.index') }}" class="m-btn m-btn--ghost">Batal</a>
        </div>
    </form>
@endsection
