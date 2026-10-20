@extends('layouts.member')

@section('title', 'Dashboard')

@section('content')
    @php
        $finished = $counts['summit'] + $counts['not_summit'];
        $summitRate = $finished > 0 ? (int) round($counts['summit'] / $finished * 100) : 0;
        $monthlyMax = max(1, max(array_column($monthly, 'count')));
        $monthlyTotal = array_sum(array_column($monthly, 'count'));
        $daysToNext = $nextClimb ? (int) $today->diffInDays($nextClimb->started_on) : null;
        $ringLength = 2 * M_PI * 80;
        $ringProgress = $nextClimb ? max(0.04, 1 - min($daysToNext, 30) / 30) : 0;
    @endphp

    <p class="m-crumb">Impeesa &middot; X-tream Adventure Team</p>
    <h1 class="m-title">Selamat datang, {{ $user->display_name }}</h1>

    @if ($invitations->isNotEmpty())
        <div style="margin-top: 22px">@include('member.climbs.invitations')</div>
    @endif

    {{-- ============ SUMMARY STRIP ============ --}}
    <div class="m-summary">
        <div class="m-pills">
            <div class="m-pill m-pill--navy" style="flex: {{ max(1, $counts['summit']) * 3 }}">
                <span class="m-pill__label">Sampai puncak</span>
                <span class="m-pill__bar">{{ $counts['summit'] }}</span>
            </div>
            <div class="m-pill m-pill--blue" style="flex: {{ max(1, $counts['not_summit']) * 3 }}">
                <span class="m-pill__label">Tidak summit</span>
                <span class="m-pill__bar">{{ $counts['not_summit'] }}</span>
            </div>
            <div class="m-pill m-pill--sky" style="flex: {{ max(1, $counts['planned']) * 3 }}">
                <span class="m-pill__label">Rencana</span>
                <span class="m-pill__bar">{{ $counts['planned'] }}</span>
            </div>
            <div class="m-pill m-pill--hatch" aria-hidden="true"><span class="m-pill__bar"></span></div>
            <a href="{{ route('photos.index') }}" class="m-pill m-pill--outline" style="flex: 2">
                <span class="m-pill__label">Foto</span>
                <span class="m-pill__bar">{{ $counts['photos'] }}</span>
            </a>
            <a href="{{ route('stories.index') }}" class="m-pill m-pill--outline" style="flex: 2">
                <span class="m-pill__label">Cerita</span>
                <span class="m-pill__bar">{{ $counts['stories'] }}</span>
            </a>
        </div>

        <div class="m-bigstats">
            <div class="m-bigstat">
                <div class="m-bigstat__meta">
                    <span class="m-bigstat__icon"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 20 6-11 4 6 3-4 5 9z"/></svg></span>
                    Pendakian
                </div>
                <span class="m-bigstat__value">{{ $totals['climbs'] }}</span>
            </div>
            <div class="m-bigstat">
                <div class="m-bigstat__meta">
                    <span class="m-bigstat__icon"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 21V4M5 4h11l-2 4 2 4H5"/></svg></span>
                    Puncak tertinggi
                </div>
                <span class="m-bigstat__value">{{ $totals['highest'] ? number_format($totals['highest'], 0, ',', '.') : '–' }}<span class="m-bigstat__unit">mdpl</span></span>
            </div>
            <div class="m-bigstat">
                <div class="m-bigstat__meta">
                    <span class="m-bigstat__icon"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/></svg></span>
                    Pendakian {{ $today->year }}
                </div>
                <span class="m-bigstat__value">{{ $totals['this_year'] }}</span>
            </div>
        </div>
    </div>

    {{-- ============ CARDS ============ --}}
    <div class="m-grid">
        {{-- Profile photo --}}
        <article class="m-card m-profile">
            @if ($user->avatar_path)
                <img class="m-profile__img" src="{{ route('avatar', $user) }}" alt="Foto {{ $user->name }}">
            @else
                <span class="m-profile__initials" aria-hidden="true">{{ $user->initials }}</span>
            @endif
            <div class="m-profile__info">
                <div>
                    <p class="m-profile__name">{{ $user->name }}</p>
                    <p class="m-profile__role">{{ $user->position ?: $user->role->label() }}</p>
                </div>
                @if ($user->member_number)
                    <span class="m-profile__badge">NIA {{ $user->member_number }}</span>
                @endif
            </div>
        </article>

        {{-- Climbed mountains map --}}
        <article class="m-card m-col-2 m-mapcard">
            <div class="m-card__head">
                <div>
                    <h2 class="m-card__title">Jejak Pendakian</h2>
                    <p class="m-list__meta">{{ count($mapPoints) }} gunung sudah didaki</p>
                </div>
                <div class="m-actions">
                    <span class="m-legend-inline"><span class="m-dot" style="background: var(--m-green)"></span>Summit</span>
                    <span class="m-legend-inline"><span class="m-dot" style="background: var(--m-red)"></span>Belum summit</span>
                    <a href="{{ route('peta') }}" class="m-card__link" aria-label="Buka Peta Gunung lengkap">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M8 7h9v9"/></svg>
                    </a>
                </div>
            </div>
            <div class="m-map" id="climbMap" aria-label="Peta gunung yang sudah didaki">
                @if (empty($mapPoints))
                    <div class="m-map__empty">
                        <p>Belum ada pendakian yang tercatat.</p>
                        <a href="{{ route('climbs.index') }}#catat" class="m-btn m-btn--primary m-btn--sm">Catat pendakian</a>
                    </div>
                @endif
            </div>
            <dialog class="m-lightbox" id="climbLightbox" aria-label="Foto pendakian">
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
            @if ($unmappedClimbs)
                <p class="m-note">Belum ada di peta karena nama gunung tidak dikenali: {{ implode(', ', $unmappedClimbs) }}.</p>
            @endif
        </article>

        {{-- Shared gallery slideshow --}}
        <article class="m-card m-slides" aria-roledescription="carousel" aria-label="Galeri Bersama">
            <div class="m-card__head">
                <h2 class="m-card__title">Galeri Bersama</h2>
                <a href="{{ route('photos.shared') }}" class="m-card__link" aria-label="Buka Galeri Bersama">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M8 7h9v9"/></svg>
                </a>
            </div>
            @if ($sharedPhotos->isEmpty())
                <div class="m-slides__stage m-slides__stage--empty">
                    <p>Belum ada foto bersama.</p>
                    <a href="{{ route('photos.index') }}" class="m-btn m-btn--ghost m-btn--sm">Bagikan fotomu</a>
                </div>
            @else
                <div class="m-slides__stage" id="sharedSlides">
                    @foreach ($sharedPhotos as $i => $photo)
                        <a href="{{ route('photos.shared') }}" @class(['m-slides__slide', 'is-active' => $i === 0]) aria-hidden="{{ $i === 0 ? 'false' : 'true' }}" tabindex="{{ $i === 0 ? '0' : '-1' }}">
                            <img src="{{ route('photos.show', $photo) }}" alt="{{ $photo->caption }}" @if ($i > 1) loading="lazy" @endif>
                            <span class="m-slides__caption">
                                <strong>{{ $photo->caption ?: 'Foto bersama' }}</strong>
                                <span>dari {{ ($photo->user ?? $photo->uploader)?->display_name ?? 'member' }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                @if ($sharedPhotos->count() > 1)
                    <div class="m-slides__dots" role="tablist" aria-label="Pilih foto">
                        @foreach ($sharedPhotos as $i => $photo)
                            <button type="button" @class(['is-active' => $i === 0]) data-slide="{{ $i }}" aria-label="Foto {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                @endif
            @endif
        </article>

        {{-- Summit rate --}}
        <article class="m-card">
            <div class="m-rate">
                <h2 class="m-card__title">Rekam Jejak</h2>
                <span class="m-rate__value">{{ $summitRate }}% summit</span>
            </div>
            <div class="m-segbar" aria-hidden="true">
                <span style="flex: {{ $counts['summit'] }}; background: var(--m-green)"></span>
                <span style="flex: {{ $counts['planned'] }}; background: var(--m-navy)"></span>
                <span style="flex: {{ $counts['not_summit'] }}; background: var(--m-red)"></span>
                @if ($counts['summit'] + $counts['planned'] + $counts['not_summit'] === 0)
                    <span style="flex: 1; background: var(--m-line)"></span>
                @endif
            </div>
            <ul class="m-legend">
                <li><span class="m-dot" style="background: var(--m-green)"></span><span>Sampai puncak</span><span>{{ $counts['summit'] }} kali</span></li>
                <li><span class="m-dot" style="background: var(--m-navy)"></span><span>Rencana</span><span>{{ $counts['planned'] }} kali</span></li>
                <li><span class="m-dot" style="background: var(--m-red)"></span><span>Tidak sampai puncak</span><span>{{ $counts['not_summit'] }} kali</span></li>
            </ul>
            <p class="m-note">Pulang dengan selamat adalah puncak yang sesungguhnya.</p>
        </article>

        {{-- Monthly climbs --}}
        <article class="m-card">
            <div class="m-card__head">
                <h2 class="m-card__title">Aktivitas Pendakian</h2>
                <a href="{{ route('climbs.index') }}" class="m-card__link" aria-label="Lihat semua pendakian">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M8 7h9v9"/></svg>
                </a>
            </div>
            <div class="m-bignum">
                <strong>{{ $monthlyTotal }}</strong>
                <span>pendakian<br>7 bulan terakhir</span>
            </div>
            <div class="m-bars">
                <span class="m-chip m-chip--solid m-bars__flag">{{ end($monthly)['count'] }} bulan ini</span>
                @foreach ($monthly as $month)
                    <div @class(['m-bars__col', 'is-current' => $month['current']]) title="{{ $month['count'] }} pendakian">
                        <span @class(['m-bars__bar', 'has-value' => $month['count'] > 0]) style="height: {{ $month['count'] > 0 ? max(10, $month['count'] / $monthlyMax * 100) : 6 }}%"></span>
                        <span class="m-bars__label">{{ $month['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        {{-- Next climb countdown --}}
        <article class="m-card">
            <div class="m-card__head">
                <h2 class="m-card__title">Pendakian Berikutnya</h2>
                @if ($nextClimb)
                    <span class="m-chip">{{ $nextClimb->started_on->translatedFormat('d M') }}@if ($nextClimb->finished_on && ! $nextClimb->finished_on->isSameDay($nextClimb->started_on))–{{ $nextClimb->finished_on->translatedFormat('d M') }}@endif</span>
                @endif
            </div>
            <div class="m-ring">
                <svg viewBox="0 0 200 200" aria-hidden="true">
                    <circle class="m-ring__ticks" cx="100" cy="100" r="88" fill="none" stroke-width="8"/>
                    <circle class="m-ring__value" cx="100" cy="100" r="80" fill="none" stroke-width="12"
                            stroke-dasharray="{{ $ringLength }}" stroke-dashoffset="{{ $ringLength * (1 - $ringProgress) }}"/>
                </svg>
                <div class="m-ring__center">
                    @if ($nextClimb)
                        <span class="m-ring__big">{{ $daysToNext === 0 ? 'Hari ini' : 'H-'.$daysToNext }}</span>
                        <span class="m-ring__small">{{ $nextClimb->started_on->translatedFormat('l, d M') }}</span>
                    @else
                        <span class="m-ring__big">–</span>
                        <span class="m-ring__small">Belum ada rencana</span>
                    @endif
                </div>
            </div>
            <p class="m-ring__caption">
                @if ($nextClimb)
                    {{ $nextClimb->mountain_name }}@if ($nextClimb->route) &middot; via {{ $nextClimb->route }}@endif
                    @if (($companions = $nextClimb->companions())->isNotEmpty())
                        <br><span style="font-size: 12px">bersama {{ $companions->map->display_name->implode(', ') }}</span>
                    @endif
                @else
                    <a href="{{ route('climbs.index') }}#catat" style="color: var(--m-blue); font-weight: 600">Catat rencana pendakian &rarr;</a>
                @endif
            </p>
        </article>

        {{-- Recent members (admin) / recent climbs (member) --}}
        <article class="m-card">
            @if ($user->isSuperAdmin())
                <div class="m-card__head">
                    <h2 class="m-card__title">Anggota Terbaru</h2>
                    <span class="m-count">{{ $recentMembers->count() }}/{{ $memberCount }}</span>
                </div>
                <ul class="m-list">
                    @foreach ($recentMembers as $member)
                        <li>
                            <span class="m-list__avatar">{{ $member->initials }}</span>
                            <a href="{{ route('admin.members.show', $member) }}">
                                <p class="m-list__title">{{ $member->name }}</p>
                                <p class="m-list__meta">{{ $member->member_number ? 'NIA '.$member->member_number.' · ' : '' }}bergabung {{ $member->created_at->translatedFormat('d M Y') }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="m-note">{{ $featuredCount }} cerita tampil di halaman utama. <a href="{{ route('admin.stories.index') }}" style="color: var(--m-blue); font-weight: 600">Kelola</a></p>
                @if ($pendingPhotoCount > 0)
                    <p class="m-note"><span class="m-chip m-chip--accent">{{ $pendingPhotoCount }} foto</span> menunggu persetujuan Galeri Bersama. <a href="{{ route('photos.shared') }}" style="color: var(--m-blue); font-weight: 600">Tinjau</a></p>
                @endif
            @else
                <div class="m-card__head">
                    <h2 class="m-card__title">Pendakian Terakhir</h2>
                    <span class="m-count">{{ $totals['climbs'] }}</span>
                </div>
                @if ($recentClimbs->isEmpty())
                    <p class="m-empty">Belum ada catatan pendakian.</p>
                @else
                    <ul class="m-list">
                        @foreach ($recentClimbs as $climb)
                            <li>
                                <span class="m-list__avatar">{{ \Illuminate\Support\Str::of($climb->mountain_name)->replace('Gunung ', '')->substr(0, 2)->upper() }}</span>
                                <div>
                                    <p class="m-list__title">{{ $climb->mountain_name }}</p>
                                    <p class="m-list__meta">{{ $climb->route ? 'via '.$climb->route.' · ' : '' }}{{ $climb->started_on->translatedFormat('d M Y') }}</p>
                                </div>
                                <span @class(['m-chip', 'm-chip--green' => $climb->status === \App\Enums\ClimbStatus::Summit, 'm-chip--red' => $climb->status === \App\Enums\ClimbStatus::NotSummit]) style="margin-left: auto">{{ $climb->elevation ? number_format($climb->elevation, 0, ',', '.') : '–' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </article>

        {{-- Personal data --}}
        <article class="m-card">
            <details class="m-acc" open>
                <summary>Data Diri</summary>
                <dl class="m-dl">
                    <div><dt>NIA</dt><dd>{{ $user->member_number ?: '–' }}</dd></div>
                    <div><dt>Nama</dt><dd>{{ $user->name }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                    <div><dt>Telepon</dt><dd>{{ $user->phone ?: '–' }}</dd></div>
                    <div><dt>Gol. darah</dt><dd>{{ $user->blood_type ?: '–' }}</dd></div>
                    <div><dt>Kota</dt><dd>{{ $user->city ?: '–' }}</dd></div>
                </dl>
            </details>
            <details class="m-acc">
                <summary>Kontak Darurat</summary>
                <dl class="m-dl">
                    <div><dt>Nama</dt><dd>{{ $user->emergency_contact_name ?: '–' }}</dd></div>
                    <div><dt>Telepon</dt><dd>{{ $user->emergency_contact_phone ?: '–' }}</dd></div>
                    <div><dt>Catatan medis</dt><dd>{{ $user->medical_notes ?: '–' }}</dd></div>
                </dl>
            </details>
            <details class="m-acc">
                <summary>Status Akun</summary>
                <dl class="m-dl">
                    <div><dt>Peran</dt><dd>{{ $user->role->label() }}</dd></div>
                    <div><dt>Jabatan</dt><dd>{{ $user->position ?: '–' }}</dd></div>
                    <div><dt>Bergabung</dt><dd>{{ $user->joined_on?->translatedFormat('d F Y') ?? '–' }}</dd></div>
                </dl>
            </details>
            <a href="{{ route('profile.edit') }}" class="m-btn m-btn--ghost m-btn--sm" style="margin-top: 12px">Ubah data diri</a>
        </article>

        {{-- Week calendar --}}
        <article class="m-card m-col-2">
            <div class="m-card__head">
                <span></span>
                <h2 class="m-card__title">{{ $today->translatedFormat('F Y') }}</h2>
                <span class="m-chip">Minggu ini</span>
            </div>
            <div class="m-week">
                @foreach ($week as $day)
                    <div @class(['m-week__day', 'is-today' => $day['date']->isSameDay($today), 'has-climb' => $day['climbs']->isNotEmpty()])>
                        <span class="m-week__name">{{ $day['date']->translatedFormat('D') }}</span>
                        <span class="m-week__num">{{ $day['date']->day }}</span>
                        <div class="m-week__chips">
                            @foreach ($day['climbs'] as $climb)
                                <span @class(['m-week__chip', 'm-week__chip--planned' => $climb->status === \App\Enums\ClimbStatus::Planned]) title="{{ $climb->mountain_name }}">{{ $climb->mountain_name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="m-divider"></div>
            <p class="m-field__label" style="margin-bottom: 10px">Agenda bulan ini</p>
            @if ($agenda->isEmpty())
                <p class="m-empty">Belum ada pendakian bulan ini.</p>
            @else
                <div class="m-agenda">
                    @foreach ($agenda as $climb)
                        <div class="m-agenda__item">
                            <strong>{{ $climb->mountain_name }}</strong>
                            <span>{{ $climb->started_on->translatedFormat('d M') }} &middot; {{ $climb->status->label() }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </article>

        {{-- Private gallery preview --}}
        <article class="m-card">
            <div class="m-card__head">
                <div>
                    <h2 class="m-card__title">Galeri Pribadi</h2>
                    <span class="m-lock">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        Hanya kamu dan super admin
                    </span>
                </div>
                <a href="{{ route('photos.index') }}" class="m-card__link" aria-label="Buka galeri">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 17 17 7M8 7h9v9"/></svg>
                </a>
            </div>
            @if ($recentPhotos->isEmpty())
                <p class="m-empty">Belum ada foto. <a href="{{ route('photos.index') }}" style="color: var(--m-blue); font-weight: 600">Unggah foto pertamamu</a></p>
            @else
                <div class="m-thumbs">
                    @foreach ($recentPhotos as $photo)
                        <a href="{{ route('photos.show', $photo) }}" target="_blank" rel="noopener">
                            <img src="{{ route('photos.show', $photo) }}" alt="{{ $photo->caption }}" loading="lazy">
                        </a>
                    @endforeach
                </div>
            @endif
        </article>

        {{-- Story CTA --}}
        <article class="m-card m-col-4" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; background: linear-gradient(120deg, var(--m-navy), var(--m-blue)); color: #fff">
            <div>
                <h2 class="m-card__title">Cerita Pendakian</h2>
                <p style="margin-top: 8px; opacity: .85; line-height: 1.6">Tulis pengalaman kecilmu di gunung. Cerita terpilih akan ditampilkan super admin di halaman utama Impeesa.</p>
            </div>
            <div class="m-actions">
                <a href="{{ route('stories.create') }}" class="m-btn" style="background: #fff; color: var(--m-navy)">Tulis cerita</a>
                <a href="{{ route('stories.index') }}" class="m-btn" style="border-color: rgba(255,255,255,.5); color: #fff">Cerita saya ({{ $counts['stories'] }})</a>
            </div>
        </article>
    </div>
@endsection

@push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@push('scripts')
    @if ($mapPoints)
        <script>
            window.CLIMB_POINTS = @json($mapPoints);
        </script>
    @endif
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="{{ asset('js/dashboard-map.js') }}"></script>
    <script>
        // Slideshow kecil Galeri Bersama: ganti foto tiap 4,5 detik, berhenti saat disentuh kursor.
        (() => {
            const stage = document.getElementById('sharedSlides');
            if (!stage) return;
            const slides = [...stage.children];
            const dots = [...document.querySelectorAll('.m-slides__dots button')];
            if (slides.length < 2) return;
            let active = 0;
            let timer = null;
            const show = (index) => {
                active = (index + slides.length) % slides.length;
                slides.forEach((slide, i) => {
                    slide.classList.toggle('is-active', i === active);
                    slide.setAttribute('aria-hidden', String(i !== active));
                    slide.tabIndex = i === active ? 0 : -1;
                });
                dots.forEach((dot, i) => dot.classList.toggle('is-active', i === active));
            };
            const play = () => { clearInterval(timer); timer = setInterval(() => show(active + 1), 4500); };
            dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); play(); }));
            stage.parentElement.addEventListener('mouseenter', () => clearInterval(timer));
            stage.parentElement.addEventListener('mouseleave', play);
            if (!matchMedia('(prefers-reduced-motion: reduce)').matches) play();
        })();
    </script>
@endpush
