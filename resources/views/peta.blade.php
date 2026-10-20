@extends('layouts.site')

@section('title', 'Peta Gunung — Impeesa X-tream Adventure Team')
@section('description', 'Peta dan history '.$trails->count().' jalur pendakian gunung di Indonesia: track GPS (GPX), file Google Earth (KMZ) dan deskripsi rute (PDF).')
@section('body-class', 'page-peta')

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@section('content')
    {{-- ============ HERO ============ --}}
    <section class="peta-hero">
        <div class="peta-hero__media" style="--teaser-bg: url('{{ asset('images/gunung/sumbing.jpg') }}')"></div>
        <div class="container peta-hero__content">
            <p class="peta-hero__eyebrow">History jalur pendakian</p>
            <h1 class="peta-hero__title">Peta Gunung</h1>
            <p class="peta-hero__text">Track GPS, file Google Earth dan deskripsi rute gunung di seluruh Indonesia, siap diunduh untuk merencanakan pendakianmu.</p>

            <dl class="stats">
                <div class="stats__item"><dt>Jalur</dt><dd>{{ $trails->count() }}</dd></div>
                <div class="stats__item"><dt>Wilayah</dt><dd>{{ $regions->count() }}</dd></div>
                <div class="stats__item"><dt>Dokumen PDF</dt><dd>{{ $pdfCount }}</dd></div>
            </dl>
        </div>
    </section>

    {{-- ============ EXPLORER ============ --}}
    <section class="explorer" id="cari">
        <div class="container">
            <div class="explorer__controls">
                <label class="search">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input type="search" id="trailSearch" placeholder="Cari gunung atau jalur..." autocomplete="off" aria-label="Cari gunung atau jalur">
                </label>

                <div class="chips" id="regionChips" role="group" aria-label="Filter wilayah">
                    <button type="button" class="chip is-active" data-region="">Semua <span>{{ $trails->count() }}</span></button>
                    @foreach ($regions as $region => $items)
                        <button type="button" class="chip" data-region="{{ $region }}">{{ $region }} <span>{{ $items->count() }}</span></button>
                    @endforeach
                </div>

                <label class="toggle">
                    <input type="checkbox" id="pdfOnly">
                    <span class="toggle__track"><span class="toggle__thumb"></span></span>
                    Hanya yang punya PDF
                </label>
            </div>

            <div class="explorer__layout">
                <div class="explorer__map">
                    <div id="map" class="map" aria-label="Peta lokasi jalur pendakian"></div>
                    <p class="explorer__result" id="resultCount" aria-live="polite">Menampilkan {{ $trails->count() }} jalur</p>
                </div>

                <div class="explorer__list" id="trailList">
                    @foreach ($regions as $region => $items)
                        <details class="region" data-region-group="{{ $region }}" @if ($loop->first) open @endif>
                            <summary class="region__head">
                                <h2 class="region__title">{{ $region }}</h2>
                                <span class="region__count"><span data-region-count>{{ $items->count() }}</span> jalur</span>
                                <svg class="region__chevron" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </summary>

                            <div class="region__grid">
                                @foreach ($items as $trail)
                                    <article class="trail" data-id="{{ $trail['id'] }}" data-region="{{ $trail['region'] }}" data-search="{{ strtolower($trail['name'].' '.$trail['area']) }}" data-pdf="{{ isset($trail['files']['pdf']) ? 1 : 0 }}">
                                        <div class="trail__thumb {{ $trail['photo'] ? '' : 'trail__thumb--topo' }}">
                                            @if ($trail['photo'])
                                                <img src="{{ $trail['photo'] }}" alt="" loading="lazy">
                                            @else
                                                <span>{{ mb_substr($trail['name'], 0, 1) }}</span>
                                            @endif
                                        </div>
                                        <div class="trail__body">
                                            <h3 class="trail__name">{{ $trail['name'] }}</h3>
                                            <p class="trail__area">{{ $trail['area'] }}</p>
                                            <div class="trail__files">
                                                @foreach (['gpx', 'kmz', 'pdf'] as $format)
                                                    @isset($trail['files'][$format])
                                                        <a href="{{ $trail['files'][$format] }}" class="file file--{{ $format }}" target="_blank" rel="noopener" download>{{ strtoupper($format) }}</a>
                                                    @else
                                                        <span class="file file--off" title="{{ strtoupper($format) }} belum tersedia">{{ strtoupper($format) }}</span>
                                                    @endisset
                                                @endforeach
                                            </div>
                                        </div>
                                        <button type="button" class="trail__locate" data-locate="{{ $trail['id'] }}" aria-label="Lihat {{ $trail['name'] }} di peta">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
                                        </button>
                                    </article>
                                @endforeach
                            </div>
                        </details>
                    @endforeach

                    <div class="empty" id="emptyState" hidden>
                        <p class="empty__title">Jalur tidak ditemukan</p>
                        <p>Coba kata kunci lain atau pilih wilayah yang berbeda.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ GOOGLE MAPS MODAL ============ --}}
    <dialog class="gmaps" id="gmapsModal" aria-labelledby="gmapsTitle">
        <div class="gmaps__head">
            <div>
                <p class="gmaps__eyebrow">Peta jalur pendakian</p>
                <h2 class="gmaps__title" id="gmapsTitle"></h2>
                <p class="gmaps__area" id="gmapsArea"></p>
            </div>
            <button type="button" class="gmaps__close" data-close aria-label="Tutup">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="gmaps__tabs" role="tablist">
            <button type="button" class="gmaps__tab is-active" role="tab" aria-selected="true" data-tab="track">Jalur GPX · Satelit</button>
            <button type="button" class="gmaps__tab" role="tab" aria-selected="false" data-tab="google">Google Maps</button>
        </div>
        <div class="gmaps__frame">
            <div class="gmaps__pane is-active" data-pane="track">
                <div id="trackMap" class="track-map"></div>
                <p class="gmaps__status" id="trackStatus" hidden></p>
            </div>
            <div class="gmaps__pane" data-pane="google">
                <iframe id="gmapsFrame" title="Google Maps satelit" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
            </div>
        </div>
        <div class="gmaps__foot">
            <p id="trackInfo">Garis biru adalah lintasan GPX, penanda oranye adalah titik waypoint.</p>
            <div class="gmaps__actions">
                <a href="#" class="btn btn--ghost btn--sm" id="trackDownload" target="_blank" rel="noopener" download>Unduh GPX</a>
                <a href="#" class="btn btn--primary btn--sm" id="gmapsExternal" target="_blank" rel="noopener">Buka di Google Maps</a>
            </div>
        </div>
    </dialog>

    {{-- ============ SOURCE ============ --}}
    <section class="source">
        <div class="container source__inner reveal">
            <div>
                <p class="source__eyebrow">Sumber data</p>
                <p class="source__text">
                    Data jalur berasal dari <strong>Bank Data Jalur Pendakian Gunung</strong> milik {{ $source['name'] }},
                    diinisiasi dan dikumpulkan oleh {{ $source['curator'] }} serta diperbarui oleh anggota Astacala.
                    Berkas diunduh langsung dari server Astacala. Posisi penanda di peta adalah titik tengah track,
                    bukan koordinat puncak.
                </p>
            </div>
            <a href="{{ $source['url'] }}" class="btn btn--ghost" target="_blank" rel="noopener">Lihat sumber asli</a>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        window.TRAILS = @json($trails->values());
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" defer></script>
    <script src="{{ asset('js/peta.js') }}" defer></script>
@endpush
