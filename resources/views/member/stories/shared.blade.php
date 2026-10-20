@extends('layouts.member')

@section('title', 'Cerita Bersama')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Member &middot; Cerita Bersama</p>
            <h1 class="m-title">Cerita bersama</h1>
            <p class="m-subtitle">Cerita pendakian yang dibagikan publik oleh anggota Impeesa.</p>
        </div>
        <a href="{{ route('stories.create') }}" class="m-btn m-btn--primary">Tulis cerita</a>
    </div>

    @if ($stories->isEmpty())
        <div class="m-card"><p class="m-empty">Belum ada cerita publik. Jadilah yang pertama berbagi pengalaman mendaki.</p></div>
    @else
        <div class="m-stories">
            @foreach ($stories as $story)
                <x-story-card :story="$story" show-author>
                    <a href="{{ route('stories.public', $story) }}" class="m-btn m-btn--ghost m-btn--sm">Baca cerita</a>
                    @can('update', $story)
                        <a href="{{ route('stories.edit', $story) }}" class="m-btn m-btn--ghost m-btn--sm">Ubah</a>
                    @endcan
                </x-story-card>
            @endforeach
        </div>
        <div class="m-pagination">{{ $stories->links('pagination::simple-bootstrap-5') }}</div>
    @endif
@endsection
