@extends('layouts.member')

@section('title', 'Ubah Pendakian')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb"><a href="{{ route('climbs.index') }}">Pendakian</a> &middot; Ubah</p>
            <h1 class="m-title">{{ $climb->mountain_name }}</h1>
        </div>
    </div>

    <div class="m-layout">
        <div>
            <h2 class="m-card__title" style="margin-bottom: 14px">Foto pendakian ({{ $climb->photos->count() }})</h2>
            @if ($climb->photos->isEmpty())
                <div class="m-card"><p class="m-empty">Belum ada foto. Tambahkan lewat formulir di samping; foto akan tampil di peta dashboard.</p></div>
            @else
                @include('member.photos.grid', [
                    'photos' => $climb->photos->sortByDesc(fn ($photo) => $photo->id === $climb->cover_photo_id),
                    'coverFor' => $climb,
                ])
            @endif
        </div>

        <form method="POST" action="{{ route('climbs.update', $climb) }}" enctype="multipart/form-data" class="m-card m-form m-sticky">
            @csrf
            @method('PUT')
            @include('member.partials.errors')
            @include('member.climbs.fields')
            <div class="m-actions">
                <button type="submit" class="m-btn m-btn--primary">Simpan perubahan</button>
                <a href="{{ route('climbs.index') }}" class="m-btn m-btn--ghost">Batal</a>
            </div>
        </form>
    </div>
@endsection
