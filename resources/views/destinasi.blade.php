@extends('layouts.site')

@section('title', $destination['name'].' — Impeesa X-tream Adventure Team')
@section('description', $destination['name'].': jalur pendakian, paket trekking dan kontak operator lokal '.$partner['name'].'.')
@section('body-class', 'page-destinasi')

@section('content')
    @php($waUrl = 'https://wa.me/'.$partner['contact']['whatsapp'].'?text='.rawurlencode('Halo '.$partner['name'].', saya ingin bertanya tentang trekking '.$destination['name'].'.'))

    {{-- ============ HERO ============ --}}
    <section class="peta-hero">
        <div class="peta-hero__media" style="--teaser-bg: url('{{ asset($destination['image']) }}')"></div>
        <div class="container peta-hero__content">
            <p class="peta-hero__eyebrow">{{ $destination['region'] }} &middot; {{ $destination['elevation'] }}</p>
            <h1 class="peta-hero__title">{{ $destination['name'] }}</h1>
            <p class="peta-hero__text">{{ $destination['description'] }}</p>

            <div class="dest-hero__actions">
                <a href="{{ $partner['url'] }}" class="btn btn--primary" target="_blank" rel="noopener">Info lengkap di {{ $partner['name'] }}</a>
                <a href="{{ $waUrl }}" class="btn btn--ghost" target="_blank" rel="noopener">Tanya via WhatsApp</a>
            </div>

            <dl class="stats">
                @foreach ($partner['stats'] as $stat)
                    <div class="stats__item"><dt>{{ $stat['label'] }}</dt><dd>{{ $stat['value'] }}</dd></div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- ============ PARTNER ============ --}}
    <section class="dest-section">
        <div class="container dest-about reveal">
            <div>
                <p class="dest-eyebrow">Operator lokal</p>
                <h2 class="dest-title">{{ $partner['name'] }}</h2>
                <p class="dest-lead">{{ $partner['tagline'] }}</p>
            </div>
            <div class="dest-about__body">
                <p>{{ $partner['about'] }}</p>
                <p>{{ $partner['basecamp'] }}</p>
                <ul class="dest-includes">
                    @foreach ($partner['includes'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    {{-- ============ ROUTES ============ --}}
    <section class="dest-section">
        <div class="container">
            <div class="section-head reveal">
                <p class="section-head__eyebrow">Pilih jalur sesuai kemampuanmu</p>
                <h2 class="section-head__title">Jalur Pendakian</h2>
            </div>
            <div class="dest-grid">
                @foreach ($partner['routes'] as $i => $route)
                    <article class="dest-card reveal" style="--delay: {{ $i * 90 }}ms">
                        <span class="dest-card__label">{{ $route['label'] }}</span>
                        <h3 class="dest-card__title">{{ $route['name'] }}</h3>
                        <p class="dest-card__text">{{ $route['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ PACKAGES ============ --}}
    <section class="dest-section">
        <div class="container">
            <div class="section-head reveal">
                <p class="section-head__eyebrow">Paket dari {{ $partner['name'] }}</p>
                <h2 class="section-head__title">Paket Trekking</h2>
            </div>
            <div class="dest-packages">
                @foreach ($partner['packages'] as $i => $package)
                    <a href="{{ $partner['url'] }}" class="dest-package reveal" style="--delay: {{ $i * 70 }}ms" target="_blank" rel="noopener">
                        <span class="dest-package__duration">{{ $package['duration'] }}</span>
                        <span class="dest-package__name">{{ $package['name'] }}</span>
                        <span class="dest-package__route">Jalur {{ $package['route'] }}</span>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9"/></svg>
                    </a>
                @endforeach
            </div>
            <p class="dest-note">Harga, itinerary dan ketersediaan tanggal dapat berubah. Cek informasi terbaru langsung di situs {{ $partner['name'] }}.</p>
        </div>
    </section>

    {{-- ============ CTA ============ --}}
    <section class="dest-section">
        <div class="container">
            <div class="teaser dest-cta reveal">
                <span class="teaser__media" style="--teaser-bg: url('{{ asset('images/gunung/segara-anak.jpg') }}')"></span>
                <span class="teaser__content">
                    <span class="teaser__eyebrow">Siap mendaki {{ $destination['name'] }}?</span>
                    <span class="teaser__title">{{ $partner['name'] }}</span>
                    <span class="teaser__text">Informasi lengkap jalur, harga paket, itinerary dan pemesanan tersedia di situs resmi {{ $partner['name'] }}.</span>
                    <span class="dest-hero__actions">
                        <a href="{{ $partner['url'] }}" class="btn btn--primary" target="_blank" rel="noopener">Kunjungi {{ parse_url($partner['url'], PHP_URL_HOST) }}</a>
                        <a href="{{ $waUrl }}" class="btn btn--ghost" target="_blank" rel="noopener">WhatsApp</a>
                    </span>
                </span>
            </div>

            <dl class="dest-contact reveal">
                <div><dt>WhatsApp</dt><dd><a href="{{ $waUrl }}" target="_blank" rel="noopener">{{ $partner['contact']['whatsapp_label'] }}</a></dd></div>
                <div><dt>Email</dt><dd><a href="mailto:{{ $partner['contact']['email'] }}">{{ $partner['contact']['email'] }}</a></dd></div>
                <div><dt>Instagram</dt><dd><a href="https://instagram.com/{{ $partner['contact']['instagram'] }}" target="_blank" rel="noopener">&#64;{{ $partner['contact']['instagram'] }}</a></dd></div>
                <div><dt>Alamat</dt><dd>{{ $partner['contact']['address'] }}</dd></div>
            </dl>
        </div>
    </section>
@endsection

@section('footer-credits')
    <span>Foto: {{ $destination['credit']['author'] }}, {{ $destination['credit']['license'] }}, via Wikimedia Commons</span>
@endsection
