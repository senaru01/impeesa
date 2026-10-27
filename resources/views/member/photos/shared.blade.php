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

    @if ($explore['slides'])
        <section class="m-card m-explore" id="galleryExplorer" aria-roledescription="carousel" aria-label="Jelajah Galeri Bersama">
            <div class="m-card__head m-explore__head">
                <div>
                    <h2 class="m-card__title">Jelajah galeri</h2>
                    <p class="m-list__meta">{{ count($explore['slides']) }} foto dari {{ count($explore['places']) }} lokasi &middot; urut sesuai waktu, ikuti jejaknya di peta</p>
                </div>
                <div class="m-explore__filters">
                    <label class="m-explore__filter">
                        <span>Lokasi</span>
                        <select class="m-input" data-explore-filter="place">
                            <option value="">Semua lokasi</option>
                            @foreach ($explore['places'] as $i => $place)
                                <option value="{{ $i }}">{{ $place['name'] }} ({{ $place['count'] }})</option>
                            @endforeach
                            @if (collect($explore['slides'])->contains('place', null))
                                <option value="none">Tanpa lokasi</option>
                            @endif
                        </select>
                    </label>
                    @if (count($explore['members']) > 1)
                        <label class="m-explore__filter">
                            <span>Dari</span>
                            <select class="m-input" data-explore-filter="member">
                                <option value="">Semua anggota</option>
                                @foreach ($explore['members'] as $member)
                                    <option value="{{ $member['id'] }}">{{ $member['name'] }} ({{ $member['count'] }})</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                </div>
            </div>

            <div class="m-explore__frame">
                <div class="m-explore__stage" tabindex="0" aria-label="Slideshow foto; gunakan panah kiri/kanan">
                    <div class="m-explore__slide is-active"><img class="m-explore__bg" alt="" aria-hidden="true"><img class="m-explore__img" alt=""></div>
                    <div class="m-explore__slide"><img class="m-explore__bg" alt="" aria-hidden="true"><img class="m-explore__img" alt=""></div>

                    <span class="m-explore__counter" data-explore="counter" aria-live="polite"></span>
                    <div class="m-explore__tools">
                        <button type="button" class="m-explore__btn" data-explore="play" aria-pressed="true" aria-label="Jeda slideshow">
                            <svg class="i-pause" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                            <svg class="i-play" viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M8 5.5v13a1 1 0 0 0 1.5.86l10.5-6.5a1 1 0 0 0 0-1.72L9.5 4.64A1 1 0 0 0 8 5.5z"/></svg>
                        </button>
                        <button type="button" class="m-explore__btn" data-explore="zoom" aria-label="Layar penuh">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
                        </button>
                    </div>
                    <button type="button" class="m-explore__btn m-explore__btn--prev" data-explore="prev" aria-label="Foto sebelumnya">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="m-explore__btn m-explore__btn--next" data-explore="next" aria-label="Foto berikutnya">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                    <div class="m-explore__caption">
                        <strong data-explore="caption"></strong>
                        <span data-explore="meta"></span>
                    </div>
                    <span class="m-explore__progress" aria-hidden="true"><span></span></span>
                </div>

                <div class="m-explore__mapwrap">
                    <div class="m-map m-explore__map" id="exploreMap" aria-label="Peta jejak foto; klik titik untuk melihat fotonya"></div>
                    <span class="m-explore__where" data-explore="where"></span>
                </div>
            </div>

            <div class="m-explore__strip" data-explore="strip" role="tablist" aria-label="Pilih foto"></div>

            <dialog class="m-lightbox" id="exploreLightbox" aria-label="Foto Galeri Bersama">
                <figure>
                    <img src="" alt="">
                    <figcaption><span data-lb-caption></span><span data-lb-counter></span></figcaption>
                </figure>
                <button type="button" class="m-lightbox__btn m-lightbox__btn--close" data-lb="close" aria-label="Tutup">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
                <button type="button" class="m-lightbox__btn m-lightbox__btn--prev" data-lb="prev" aria-label="Foto sebelumnya">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button type="button" class="m-lightbox__btn m-lightbox__btn--next" data-lb="next" aria-label="Foto berikutnya">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </dialog>
        </section>
    @endif

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
                            <figure class="m-photo" id="foto-{{ $photo->id }}" style="margin: 0">
                                <a href="{{ route('photos.show', $photo) }}" class="m-photo__img" target="_blank" rel="noopener">
                                    <img src="{{ route('photos.show', $photo) }}" alt="{{ $photo->caption }}" loading="lazy">
                                </a>
                                <figcaption class="m-photo__body" style="flex-direction: column; align-items: stretch">
                                    <div style="min-width: 0">
                                        @if ($isAdmin)
                                            @include('member.photos.caption-edit')
                                        @else
                                            <p class="m-photo__caption">{{ $photo->caption ?: 'Tanpa keterangan' }}</p>
                                        @endif
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

@if ($explore['slides'])
    @push('head')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    @endpush

    @push('scripts')
        <script>
            window.GALLERY_EXPLORE = @json($explore);
        </script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script src="{{ asset('js/gallery-explorer.js') }}"></script>
    @endpush
@endif
