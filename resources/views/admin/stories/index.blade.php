@extends('layouts.member')

@section('title', 'Kelola Cerita')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Cerita</p>
            <h1 class="m-title">Kelola cerita</h1>
            <p class="m-subtitle">Pilih cerita publik member untuk tampil di section "Cerita Pendakian" halaman utama; yang terbaru dipilih tampil paling depan. Cerita privat hanya bisa dibaca, tidak bisa ditampilkan.</p>
        </div>
    </div>

    @if ($stories->isEmpty())
        <div class="m-card"><p class="m-empty">Belum ada cerita dari member.</p></div>
    @else
        <div class="m-stories">
            @foreach ($stories as $story)
                <x-story-card :story="$story" show-author>
                    @include('admin.stories.feature-button')
                </x-story-card>
            @endforeach
        </div>
        <div class="m-pagination">{{ $stories->links('pagination::simple-bootstrap-5') }}</div>
    @endif
@endsection
