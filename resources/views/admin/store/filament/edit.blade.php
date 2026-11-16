@extends('layouts.member')

@section('title', 'Ubah Filament')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Toko &middot; Filament</p>
            <h1 class="m-title">{{ $lot->material }} {{ $lot->color }}</h1>
            <p class="m-subtitle">Mengubah harga tidak mengubah modal item yang sudah diproduksi sebelumnya.</p>
        </div>
        <a href="{{ route('admin.store.filament.index') }}" class="m-btn m-btn--ghost">Kembali</a>
    </div>

    @include('admin.store.nav')

    <form method="POST" action="{{ route('admin.store.filament.update', $lot) }}" class="m-card m-form" style="max-width: 720px">
        @csrf
        @method('PUT')
        @include('member.partials.errors')
        @include('admin.store.filament.fields')
        <div class="m-actions"><button type="submit" class="m-btn m-btn--primary">Simpan</button></div>
    </form>
@endsection
