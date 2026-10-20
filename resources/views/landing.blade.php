@extends('layouts.site')

@section('content')
    {{-- ============ MAIN HERO ============ --}}
    <section class="intro" id="home">
        <img class="intro__photo"
             src="{{ asset('images/hero/foto-utama-2400.jpg') }}"
             srcset="{{ asset('images/hero/foto-utama-1200.jpg') }} 1200w, {{ asset('images/hero/foto-utama-2400.jpg') }} 2400w"
             sizes="100vw"
             alt="Tim Impeesa X-tream Adventure Team di jalur pendakian Rinjani">
        <div class="intro__shade"></div>
        <div class="intro__topo" aria-hidden="true">
            <img class="topo topo--tl" src="{{ asset('images/topo/contour-a.svg') }}" alt="">
            <img class="topo topo--tr" src="{{ asset('images/topo/contour-b.svg') }}" alt="">
            <img class="topo topo--bl" src="{{ asset('images/topo/contour-b.svg') }}" alt="">
            <img class="topo topo--br" src="{{ asset('images/topo/contour-a.svg') }}" alt="">
        </div>

        <div class="intro__top">
            <h1 class="intro__title">
                <span class="intro__title-main">Impeesa</span>
                <span class="intro__title-sub"><span class="intro__accent">X-tream</span> Adventure Team</span>
            </h1>
        </div>

        <div class="intro__bottom">
            <span class="intro__divider" aria-hidden="true"></span>
            <blockquote class="intro__quote">
                <p>&ldquo;Alam rimba bukanlah tempat untuk menunjukan siapa diri kita, namun siapa diri kita akan terlihat ketika kita disana, tanamkan dalam hati dan nurani untuk selalu <strong><em>&lsquo;Berharap yang Terbaik, Bersiap untuk kondisi Terburuk&rsquo;</em></strong>&rdquo;</p>
                <footer class="intro__author">&mdash; a.rinjani_</footer>
            </blockquote>
            <a href="#galeri" class="intro__scroll" aria-label="Gulir ke galeri">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
            </a>
        </div>
    </section>

    {{-- ============ GALLERY ============ --}}
    <section class="gallery" id="galeri" aria-roledescription="carousel" aria-label="Galeri petualangan">
        <div class="container">
            <div class="gallery__head">
                <div class="reveal">
                    <p class="gallery__eyebrow">Galeri petualangan</p>
                    <h2 class="gallery__title">Jejak Langkah Kami</h2>
                </div>
                <div class="gallery__meta reveal" style="--delay: 120ms">
                    <div class="gallery__counter" aria-live="polite">
                        <span class="gallery__current" id="galleryCurrent">01</span>
                        <span class="gallery__total">/ {{ str_pad(count($gallery), 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="gallery__controls">
                        <button type="button" class="gallery__btn" id="galleryPrev" aria-label="Foto sebelumnya">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                        </button>
                        <button type="button" class="gallery__btn" id="galleryPlay" aria-label="Jeda slideshow" aria-pressed="true">
                            <svg class="icon-pause" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                            <svg class="icon-play" viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                        <button type="button" class="gallery__btn" id="galleryNext" aria-label="Foto berikutnya">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="gallery__stage reveal" id="galleryStage">
                @foreach ($gallery as $i => $photo)
                    <figure class="slide {{ $i === 0 ? 'is-active' : '' }}" data-index="{{ $i }}" style="--kb-x: {{ [-3, 3, -2, 2][$i % 4] }}%; --kb-y: {{ [2, -2, -3, 1][$i % 4] }}%">
                        <div class="slide__inner">
                            <img @if ($i < 2 || $i === count($gallery) - 1) src="{{ asset("images/gallery/{$photo['file']}.jpg") }}" @else data-src="{{ asset("images/gallery/{$photo['file']}.jpg") }}" @endif alt="{{ $photo['title'] }}" draggable="false">
                        </div>
                        <figcaption class="slide__caption">
                            <span class="slide__no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="slide__title">{{ $photo['title'] }}</span>
                            <span class="slide__text">{{ $photo['caption'] }}</span>
                        </figcaption>
                    </figure>
                @endforeach

                <button type="button" class="gallery__zoom" id="galleryZoom" aria-label="Lihat foto layar penuh">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
                </button>
            </div>

            <div class="filmstrip" id="filmstrip" role="tablist" aria-label="Pilih foto">
                @foreach ($gallery as $i => $photo)
                    <button type="button" class="filmstrip__item {{ $i === 0 ? 'is-active' : '' }}" data-index="{{ $i }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}" aria-label="{{ $photo['title'] }}">
                        <img src="{{ asset("images/gallery/{$photo['file']}-thumb.jpg") }}" alt="" loading="lazy" draggable="false">
                    </button>
                @endforeach
            </div>
        </div>

        <dialog class="lightbox" id="lightbox" aria-label="Foto layar penuh">
            <img id="lightboxImg" alt="">
            <div class="lightbox__caption">
                <span id="lightboxCount"></span>
                <strong id="lightboxTitle"></strong>
                <span id="lightboxText"></span>
            </div>
            <button type="button" class="lightbox__btn lightbox__btn--close" data-lightbox="close" aria-label="Tutup">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
            <button type="button" class="lightbox__btn lightbox__btn--prev" data-lightbox="prev" aria-label="Foto sebelumnya">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button type="button" class="lightbox__btn lightbox__btn--next" data-lightbox="next" aria-label="Foto berikutnya">
                <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </dialog>
    </section>

    {{-- ============ HERO / COVERFLOW ============ --}}
    <section class="hero" id="destinasi">
        <div class="hero__bg">
            @foreach ($destinations as $i => $destination)
                <div class="hero__bg-img {{ $i === 0 ? 'is-active' : '' }}" data-bg="{{ asset($destination['image']) }}" @if ($i === 0) style="background-image: url('{{ asset($destination['image']) }}')" @endif></div>
            @endforeach
        </div>
        <div class="hero__shade"></div>

        <div class="fog" aria-hidden="true">
            <span class="fog__cloud fog__cloud--1"></span>
            <span class="fog__cloud fog__cloud--2"></span>
            <span class="fog__cloud fog__cloud--3"></span>
            <span class="fog__cloud fog__cloud--4"></span>
        </div>
        <div class="fog-burst" id="fogBurst" aria-hidden="true"></div>

        <div class="stage" id="stage" aria-roledescription="carousel" aria-label="Destinasi gunung">
            <div class="stage__track">
                @foreach ($destinations as $i => $destination)
                    <button class="card" type="button" data-index="{{ $i }}" @if ($destination['detail_url']) data-href="{{ $destination['detail_url'] }}" @endif aria-label="{{ $destination['name'] }}, {{ $destination['region'] }}{{ $destination['detail_url'] ? ' — lihat detail' : '' }}">
                        @if ($i <= 2 || $i >= count($destinations) - 2)
                            <img src="{{ asset($destination['image']) }}" alt="">
                        @else
                            <img data-src="{{ asset($destination['image']) }}" alt="">
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="caption" id="caption" aria-live="polite">
                @foreach ($destinations as $i => $destination)
                    <div class="caption__item {{ $i === 0 ? 'is-active' : '' }}">
                        <span class="caption__meta">{{ $destination['elevation'] }}</span>
                        <h2 class="caption__title">{{ $destination['name'] }}</h2>
                        <p class="caption__region">&ndash; {{ $destination['region'] }}</p>
                        <span class="caption__line"></span>
                        <p class="caption__tagline">{{ $destination['tagline'] }}</p>
                    </div>
                @endforeach
            </div>

            <button class="stage__arrow stage__arrow--prev" id="prev" type="button" aria-label="Destinasi sebelumnya">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <button class="stage__arrow stage__arrow--next" id="next" type="button" aria-label="Destinasi berikutnya">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>

        <div class="hero__footer">
            <p class="hero__desc" id="heroDesc">{{ $destinations[0]['description'] }}</p>
            <div class="progress" role="progressbar" aria-label="Posisi slide" aria-valuemin="1" aria-valuemax="{{ count($destinations) }}" aria-valuenow="1">
                <span class="progress__fill" id="progressFill" style="width: {{ 100 / count($destinations) }}%"></span>
            </div>
            <div class="counter"><span id="counterCurrent">01</span> / {{ str_pad(count($destinations), 2, '0', STR_PAD_LEFT) }}</div>
        </div>
    </section>

    {{-- ============ RECOMMENDATIONS ============ --}}
    <section class="recs" id="recommendations">
        <div class="container">
            <div class="section-head reveal">
                <p class="section-head__eyebrow">Bingung mau mendaki ke mana? Ini pilihan kami</p>
                <h2 class="section-head__title">Rekomendasi Pendakian</h2>
            </div>

            <div class="recs__grid">
                @foreach ($recommendations as $i => $destination)
                    <article class="rec reveal" style="--delay: {{ $i * 90 }}ms">
                        @if ($destination['detail_url'])
                            <a href="{{ $destination['detail_url'] }}" class="rec__link" aria-label="{{ $destination['name'] }} — lihat detail">
                        @endif
                        <div class="rec__media">
                            <img src="{{ asset($destination['image']) }}" alt="{{ $destination['name'] }}" loading="lazy">
                            <span class="rec__rank">#{{ $i + 1 }}</span>
                            <span class="rec__elevation">{{ $destination['elevation'] }}</span>
                        </div>
                        <div class="rec__body">
                            <h3 class="rec__place">Peringkat {{ $i + 1 }}</h3>
                            <p class="rec__name">{{ $destination['name'] }}, {{ $destination['region'] }}</p>
                            @if ($destination['detail_url'])
                                <span class="rec__more">Lihat detail &rarr;</span>
                            @endif
                        </div>
                        @if ($destination['detail_url'])
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ MEMBER STORIES ============ --}}
    @if ($stories->isNotEmpty())
        <section class="tales" id="cerita">
            <div class="container">
                <div class="section-head reveal">
                    <p class="section-head__eyebrow">Pengalaman kecil dari jalur pendakian</p>
                    <h2 class="section-head__title">Cerita Pendakian</h2>
                </div>

                <div class="tales__grid">
                    @foreach ($stories as $i => $story)
                        <a href="{{ route('stories.public', $story) }}" class="tale reveal" style="--delay: {{ $i * 90 }}ms">
                            <span class="tale__media">
                                @if ($story->cover_path)
                                    <img src="{{ route('stories.cover', $story) }}" alt="" loading="lazy">
                                @endif
                            </span>
                            <span class="tale__body">
                                <span class="tale__meta">{{ $story->mountain_name ?: 'Cerita pendakian' }}@if ($story->climbed_on) &middot; {{ $story->climbed_on->translatedFormat('M Y') }}@endif</span>
                                <span class="tale__title">{{ $story->title }}</span>
                                <span class="tale__excerpt">{{ \Illuminate\Support\Str::limit($story->body, 140) }}</span>
                                <span class="tale__author">&mdash; {{ $story->user->display_name }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ MEMBER PROFILES ============ --}}
    @if ($profiles->isNotEmpty())
        <section class="crew" id="anggota">
            <div class="container">
                <div class="section-head reveal">
                    <p class="section-head__eyebrow">Kami keluarga, dan itulah keluarga kami</p>
                    <h2 class="section-head__title">Profil Singkat Anggota</h2>
                </div>

                <div class="crew__grid">
                    @foreach ($profiles as $i => $member)
                        <a href="{{ route('profiles.index') }}#anggota-{{ $member->id }}" class="crew__item reveal" style="--delay: {{ $i * 60 }}ms">
                            <img src="{{ route('profiles.photo', $member) }}" alt="Foto {{ $member->name }}" loading="lazy">
                            <span class="crew__name">{{ $member->name }}</span>
                            @if ($member->signature_place)
                                <span class="crew__place">@ {{ $member->signature_place }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                <p class="crew__more reveal">
                    <a href="{{ route('profiles.index') }}" class="btn btn--ghost">Kenalan dengan {{ $profileCount }} anggota &rarr;</a>
                </p>
            </div>
        </section>
    @endif

    {{-- ============ PETA GUNUNG TEASER ============ --}}
    <section class="teaser-wrap">
        <div class="container">
            <a href="{{ route('peta') }}" class="teaser reveal" data-morph>
                <span class="teaser__media" style="--teaser-bg: url('{{ asset('images/gunung/sumbing.jpg') }}')"></span>
                <span class="teaser__content">
                    <span class="teaser__eyebrow">History jalur pendakian</span>
                    <span class="teaser__title">Peta Gunung</span>
                    <span class="teaser__text">{{ $trailCount }} jalur pendakian di {{ $regionCount }} wilayah Indonesia, lengkap dengan track GPS, file Google Earth dan deskripsi rute.</span>
                    <span class="teaser__cta">Buka peta
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </span>
                </span>
            </a>
        </div>
    </section>

    {{-- ============ HOLIDAY CTA ============ --}}
    <section class="holiday" id="holiday" style="--holiday-bg: url('{{ asset('images/gunung/segara-anak.jpg') }}')">
        <div class="container holiday__inner">
            <div class="holiday__copy reveal">
                <h2 class="holiday__title">Mendaki dan<br>nikmati<br>liburanmu</h2>
                <a href="#recommendations" class="holiday__link">
                    <span class="holiday__link-icon">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    Pilih petualanganmu
                </a>
                <p class="holiday__text">
                    Dari lautan pasir Bromo hingga danau kawah Segara Anak di Rinjani, Indonesia punya lebih dari
                    120 gunung api aktif yang menunggu untuk dijelajahi. Siapkan perlengkapan, pilih jalurnya,
                    dan biarkan kami mengurus sisanya.
                </p>
            </div>

            <div class="holiday__gallery">
                <figure class="holiday__photo reveal" style="--delay: 100ms">
                    <img src="{{ asset('images/gunung/merbabu.jpg') }}" alt="Gunung Merbabu dari kejauhan" loading="lazy">
                    <figcaption>Gunung Merbabu, Jawa Tengah</figcaption>
                </figure>
                <figure class="holiday__photo reveal" style="--delay: 200ms">
                    <img src="{{ asset('images/gunung/rinjani.jpg') }}" alt="Anak gunung Barujari di Danau Segara Anak" loading="lazy">
                    <figcaption>Segara Anak, Rinjani</figcaption>
                </figure>
            </div>
        </div>
    </section>
@endsection

@section('footer-credits')
    <details class="credits">
        <summary>Kredit foto</summary>
        <ul>
            @foreach ($credits as $credit)
                <li><a href="{{ $credit['url'] }}" target="_blank" rel="noopener">{{ $credit['title'] }}</a> &mdash; {{ $credit['author'] }}, {{ $credit['license'] }}, via Wikimedia Commons</li>
            @endforeach
        </ul>
    </details>
@endsection

@push('scripts')
    <script>
        window.LANDING_DESCRIPTIONS = @json(array_column($destinations, 'description'));
    </script>
    <script src="{{ asset('js/landing.js') }}" defer></script>
    <script src="{{ asset('js/gallery.js') }}" defer></script>
@endpush
