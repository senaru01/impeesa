@extends('layouts.member')

@section('title', 'Cerita Saya')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Member &middot; Cerita Saya</p>
            <h1 class="m-title">Cerita saya</h1>
            <p class="m-subtitle">Cerita privat hanya untukmu dan super admin. Cerita publik bisa dibaca semua member di <a href="{{ route('stories.shared') }}" style="color: var(--m-blue); font-weight: 600">Cerita Bersama</a> dan bisa dipilih super admin untuk halaman utama.</p>
        </div>
        <a href="{{ route('stories.create') }}" class="m-btn m-btn--primary">Tulis cerita</a>
    </div>

    @if ($stories->isEmpty())
        <div class="m-card"><p class="m-empty">Belum ada cerita. Momen lucu di tenda, sunrise pertama di puncak, atau pelajaran saat turun gunung, semua layak diceritakan.</p></div>
    @else
        <div class="m-stories">
            @foreach ($stories as $story)
                <x-story-card :story="$story">
                    <div class="m-actions">
                        <a href="{{ route('stories.edit', $story) }}" class="m-btn m-btn--ghost m-btn--sm">Ubah</a>
                        <a href="{{ route('stories.public', $story) }}" class="m-btn m-btn--ghost m-btn--sm">Baca</a>
                    </div>
                    <form method="POST" action="{{ route('stories.destroy', $story) }}" onsubmit="return confirm('Hapus cerita ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="m-btn m-btn--danger m-btn--sm">Hapus</button>
                    </form>
                </x-story-card>
            @endforeach
        </div>
    @endif
@endsection
