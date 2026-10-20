@extends('layouts.member')

@section('title', 'Galeri Pribadi')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Member &middot; Galeri</p>
            <h1 class="m-title">Galeri pribadi</h1>
            <p class="m-subtitle m-lock">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                Foto di sini hanya bisa dilihat olehmu dan super admin.
            </p>
        </div>
        <span class="m-count">{{ $photos->count() }} foto</span>
    </div>

    <div class="m-layout">
        <div>
            @if ($photos->isEmpty())
                <div class="m-card"><p class="m-empty">Galeri masih kosong. Unggah foto pendakianmu lewat formulir di samping.</p></div>
            @else
                @include('member.photos.grid', ['photos' => $photos])
            @endif
        </div>

        @include('member.photos.upload-form', [
            'action' => route('photos.store'),
            'title' => 'Unggah foto',
            'class' => 'm-sticky',
        ])
    </div>
@endsection
