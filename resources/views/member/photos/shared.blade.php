@extends('layouts.member')

@section('title', 'Galeri Bersama')

@section('content')
    @php($isAdmin = auth()->user()->isSuperAdmin())

    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Member &middot; Galeri Bersama</p>
            <h1 class="m-title">Galeri bersama</h1>
            <p class="m-subtitle">Foto kegiatan tim yang bisa dilihat semua anggota Impeesa. Kiriman member tampil setelah disetujui super admin.</p>
        </div>
        <span class="m-count">{{ $photos->total() }} foto</span>
    </div>

    <div class="m-layout">
        <div style="display: grid; gap: 20px; align-content: start">
            @if ($pending->isNotEmpty())
                <section class="m-card">
                    <div class="m-card__head">
                        <h2 class="m-card__title">{{ $isAdmin ? 'Menunggu persetujuan' : 'Kirimanmu yang menunggu persetujuan' }}</h2>
                        <span class="m-chip m-chip--accent">{{ $pending->count() }} foto</span>
                    </div>
                    <div class="m-gallery">
                        @foreach ($pending as $photo)
                            <figure class="m-photo" style="margin: 0">
                                <a href="{{ route('photos.show', $photo) }}" class="m-photo__img" target="_blank" rel="noopener">
                                    <img src="{{ route('photos.show', $photo) }}" alt="{{ $photo->caption }}" loading="lazy">
                                </a>
                                <figcaption class="m-photo__body" style="flex-direction: column; align-items: stretch">
                                    <div style="min-width: 0">
                                        <p class="m-photo__caption">{{ $photo->caption ?: 'Tanpa keterangan' }}</p>
                                        <p class="m-photo__date">dari {{ ($photo->user ?? $photo->uploader)?->display_name ?? 'member' }} &middot; {{ $photo->shared_at->translatedFormat('d M Y') }}{{ $photo->isSharedOnly() ? '' : ' · dari galeri pribadi' }}</p>
                                    </div>
                                    <div class="m-actions">
                                        @if ($isAdmin)
                                            <form method="POST" action="{{ route('admin.shared-photos.approve', $photo) }}">
                                                @csrf
                                                <button type="submit" class="m-btn m-btn--primary m-btn--sm">Setujui</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.shared-photos.reject', $photo) }}" onsubmit="return confirm('{{ $photo->isSharedOnly() ? 'Tolak dan hapus foto ini?' : 'Tolak foto ini? Foto tetap ada di galeri pribadi pemiliknya.' }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="m-btn m-btn--danger m-btn--sm">Tolak</button>
                                            </form>
                                        @else
                                            <span class="m-chip m-chip--grey">Menunggu</span>
                                            <form method="POST" action="{{ $photo->isSharedOnly() ? route('photos.destroy', $photo) : route('photos.unshare', $photo) }}" onsubmit="return confirm('Batalkan kiriman foto ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="m-btn m-btn--danger m-btn--sm">Batalkan</button>
                                            </form>
                                        @endif
                                    </div>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($photos->isEmpty())
                <div class="m-card"><p class="m-empty">Belum ada foto di Galeri Bersama.</p></div>
            @else
                @include('member.photos.grid', ['photos' => $photos, 'context' => 'shared'])
                <div class="m-pagination">{{ $photos->links('pagination::simple-bootstrap-5') }}</div>
            @endif
        </div>

        @include('member.photos.upload-form', [
            'action' => route('photos.shared.store'),
            'title' => $isAdmin ? 'Tambah ke Galeri Bersama' : 'Kirim ke Galeri Bersama',
            'hint' => $isAdmin
                ? 'Foto langsung terlihat oleh semua member yang login.'
                : 'Foto akan tampil untuk semua member setelah disetujui super admin.',
            'class' => 'm-sticky',
        ])
    </div>
@endsection
