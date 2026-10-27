@extends('layouts.member')

@section('title', $member->name)

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb"><a href="{{ route('admin.members.index') }}">Anggota</a> &middot; Detail</p>
            <h1 class="m-title">{{ $member->name }}</h1>
            <p class="m-subtitle">{{ $member->position ?: $member->role->label() }}{{ $member->member_number ? ' · NIA '.$member->member_number : '' }}</p>
        </div>
        <div class="m-actions">
            @if ($member->isProfileFeatured())
                <a href="{{ route('profiles.index') }}#anggota-{{ $member->id }}" class="m-btn m-btn--ghost" target="_blank" rel="noopener">Lihat profil publik</a>
                <form method="POST" action="{{ route('admin.members.profile.unfeature', $member) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="m-btn m-btn--danger">Turunkan dari web utama</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.members.profile.feature', $member) }}">
                    @csrf
                    <button type="submit" class="m-btn m-btn--ghost">Tampilkan profil di web utama</button>
                </form>
            @endif
            <a href="{{ route('admin.members.emergency-card', $member) }}" class="m-btn m-btn--ghost">Kartu darurat</a>
            <a href="{{ route('admin.members.edit', $member) }}" class="m-btn m-btn--primary">Ubah data</a>
        </div>
    </div>

    <div class="m-grid" style="margin-bottom: 20px">
        <article class="m-card m-profile">
            @if ($member->avatar_path)
                <img class="m-profile__img" src="{{ route('avatar', $member) }}" alt="Foto {{ $member->name }}">
            @else
                <span class="m-profile__initials" aria-hidden="true">{{ $member->initials }}</span>
            @endif
            <div class="m-profile__info">
                <div>
                    <p class="m-profile__name">{{ $member->name }}</p>
                    <p class="m-profile__role">{{ $member->email }}</p>
                </div>
                <span class="m-profile__badge">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
        </article>

        <article class="m-card">
            <h2 class="m-card__title" style="margin-bottom: 14px">Data diri</h2>
            <dl class="m-dl">
                <div><dt>Panggilan</dt><dd>{{ $member->nickname ?: '–' }}</dd></div>
                <div><dt>Telepon</dt><dd>{{ $member->phone ?: '–' }}</dd></div>
                <div><dt>TTL</dt><dd>{{ collect([$member->birth_place, $member->birth_date?->translatedFormat('d M Y')])->filter()->implode(', ') ?: '–' }}</dd></div>
                <div><dt>Jenis kelamin</dt><dd>{{ ['L' => 'Laki-laki', 'P' => 'Perempuan'][$member->gender] ?? '–' }}</dd></div>
                <div><dt>Kota</dt><dd>{{ $member->city ?: '–' }}</dd></div>
                <div><dt>Bergabung</dt><dd>{{ $member->joined_on?->translatedFormat('d M Y') ?? '–' }}</dd></div>
            </dl>
        </article>

        <article class="m-card">
            <h2 class="m-card__title" style="margin-bottom: 14px">Keselamatan</h2>
            <dl class="m-dl">
                <div><dt>Gol. darah</dt><dd>{{ $member->blood_type ?: '–' }}</dd></div>
                <div><dt>Kontak darurat</dt><dd>{{ $member->emergency_contact_name ?: '–' }}</dd></div>
                <div><dt>Telepon darurat</dt><dd>{{ $member->emergency_contact_phone ?: '–' }}</dd></div>
                <div><dt>Catatan medis</dt><dd>{{ $member->medical_notes ?: '–' }}</dd></div>
            </dl>
        </article>

        <article class="m-card">
            <h2 class="m-card__title" style="margin-bottom: 14px">Ringkasan</h2>
            <dl class="m-dl">
                <div><dt>Pendakian</dt><dd>{{ $member->climbs_count }}</dd></div>
                <div><dt>Foto</dt><dd>{{ $member->photos_count }}</dd></div>
                <div><dt>Cerita</dt><dd>{{ $member->stories_count }}</dd></div>
                <div><dt>Alamat</dt><dd>{{ $member->address ?: '–' }}</dd></div>
            </dl>
            @if ($member->bio)
                <p class="m-note" style="line-height: 1.6">{{ $member->bio }}</p>
            @endif
        </article>
    </div>

    <div class="m-card" style="margin-bottom: 20px">
        <h2 class="m-card__title" style="margin-bottom: 14px">Riwayat pendakian</h2>
        @if ($climbs->isEmpty())
            <p class="m-empty">Belum ada catatan pendakian.</p>
        @else
            <div class="m-table-wrap">
                <table class="m-table">
                    <thead><tr><th>Gunung</th><th>Jalur</th><th>Tanggal</th><th>Ketinggian</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($climbs as $climb)
                            <tr>
                                <td class="m-list__title">{{ $climb->mountain_name }}</td>
                                <td>{{ $climb->route ?: '–' }}</td>
                                <td>{{ $climb->started_on->translatedFormat('d M Y') }}</td>
                                <td>{{ $climb->elevation ? number_format($climb->elevation, 0, ',', '.').' mdpl' : '–' }}</td>
                                <td>{{ $climb->status->label() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="m-grid m-grid--3" style="margin-bottom: 20px">
        <article class="m-card">
            <h2 class="m-card__title" style="margin-bottom: 14px">Kesehatan</h2>
            <dl class="m-dl">
                <div><dt>Berat terakhir</dt><dd>{{ $health['latestWeight'] ? number_format($health['latestWeight']->weight_kg, 1, ',', '.').' kg' : '–' }}</dd></div>
                <div><dt>Perubahan total</dt><dd>{{ $health['totalChange'] !== null ? ($health['totalChange'] > 0 ? '+' : '').number_format($health['totalChange'], 1, ',', '.').' kg' : '–' }}</dd></div>
                <div><dt>Tinggi badan</dt><dd>{{ $health['heightCm'] ? $health['heightCm'].' cm' : '–' }}</dd></div>
                <div><dt>IMT</dt><dd>{{ $health['bmi'] ? number_format($health['bmi'], 1, ',', '.').' ('.$health['bmiCategory'].')' : '–' }}</dd></div>
                <div><dt>Lari bulan ini</dt><dd>{{ $health['monthRuns'] }}× &middot; {{ number_format($health['monthDistance'], 1, ',', '.') }} km</dd></div>
                <div><dt>Total lari</dt><dd>{{ number_format($health['totalDistance'], 1, ',', '.') }} km</dd></div>
                <div><dt>Pace terbaik</dt><dd>{{ $health['bestPace'] ?? '–' }}</dd></div>
            </dl>
        </article>

        <article class="m-card">
            <h2 class="m-card__title" style="margin-bottom: 14px">Berat badan terakhir</h2>
            @if ($weights->isEmpty())
                <p class="m-empty">Belum ada catatan.</p>
            @else
                <dl class="m-dl">
                    @foreach ($weights as $log)
                        <div><dt>{{ $log->recorded_on->translatedFormat('d M Y') }}</dt><dd>{{ number_format($log->weight_kg, 1, ',', '.') }} kg</dd></div>
                    @endforeach
                </dl>
            @endif
        </article>

        <article class="m-card">
            <h2 class="m-card__title" style="margin-bottom: 14px">Lari terakhir</h2>
            @if ($runs->isEmpty())
                <p class="m-empty">Belum ada catatan.</p>
            @else
                <dl class="m-dl">
                    @foreach ($runs as $run)
                        <div><dt>{{ $run->run_on->translatedFormat('d M Y') }}</dt><dd>{{ number_format($run->distance_km, 2, ',', '.') }} km &middot; {{ $run->formattedPace() }}</dd></div>
                    @endforeach
                </dl>
            @endif
        </article>
    </div>

    <div class="m-pagehead" style="margin-bottom: 14px">
        <div>
            <h2 class="m-card__title">Galeri pribadi</h2>
            <p class="m-lock">Hanya terlihat oleh {{ $member->display_name }} dan super admin.</p>
        </div>
    </div>
    <div class="m-layout" style="margin-bottom: 20px">
        <div>
            @if ($photos->isEmpty())
                <div class="m-card"><p class="m-empty">Belum ada foto.</p></div>
            @else
                @include('member.photos.grid', ['photos' => $photos])
            @endif
        </div>

        @include('member.photos.upload-form', [
            'action' => route('admin.members.photos.store', $member),
            'title' => 'Kirim foto ke '.$member->display_name,
            'hint' => 'Foto masuk ke galeri pribadi '.$member->display_name.' dan hanya terlihat olehnya dan super admin.',
        ])
    </div>

    <h2 class="m-card__title" style="margin-bottom: 14px">Cerita</h2>
    @if ($stories->isEmpty())
        <div class="m-card"><p class="m-empty">Belum ada cerita.</p></div>
    @else
        <div class="m-stories">
            @foreach ($stories as $story)
                <x-story-card :story="$story">
                    @include('admin.stories.feature-button')
                </x-story-card>
            @endforeach
        </div>
    @endif
@endsection
