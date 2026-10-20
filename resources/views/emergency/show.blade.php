<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>Data Darurat — {{ $person->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/member.css') }}">
</head>
<body class="member ed">
    @php
        $phoneLink = fn (?string $phone) => $phone ? 'tel:'.preg_replace('/[^\d+]/', '', $phone) : null;
        $genders = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
    @endphp

    <header class="ed__band">DATA DARURAT &middot; IMPEESA X-TREAM ADVENTURE TEAM</header>

    <main class="ed__main">
        <section class="ed__hero">
            @if ($person->avatar_path)
                <img class="ed__photo" src="{{ route('emergency.photo', $person->emergency_token) }}" alt="Foto {{ $person->name }}">
            @else
                <span class="ed__photo ed__photo--empty">{{ $person->initials }}</span>
            @endif
            <div>
                <h1 class="ed__name">{{ $person->name }}</h1>
                <p class="ed__muted">{{ $person->nickname ? 'Panggilan: '.$person->nickname.' · ' : '' }}{{ $person->member_number ? 'NIA '.$person->member_number : 'Anggota Impeesa' }}</p>
            </div>
            <div class="ed__blood"><span>Gol. darah</span><strong>{{ $person->blood_type ?: '–' }}</strong></div>
        </section>

        <section class="ed__actions">
            @if ($person->emergency_contact_phone)
                <a href="{{ $phoneLink($person->emergency_contact_phone) }}" class="m-btn ed__call">Telepon {{ $person->emergency_contact_name ?: 'kontak darurat' }}</a>
            @endif
            <a href="tel:115" class="m-btn m-btn--ghost">Basarnas 115</a>
            <a href="tel:112" class="m-btn m-btn--ghost">Darurat 112</a>
        </section>

        <section class="m-card ed__card ed__card--alert">
            <h2 class="m-card__title">Medis</h2>
            <dl class="m-dl">
                <div><dt>Alergi</dt><dd>{{ $person->allergies ?: 'Tidak ada data' }}</dd></div>
                <div><dt>Kondisi medis</dt><dd>{{ $person->medical_notes ?: 'Tidak ada data' }}</dd></div>
                <div><dt>Obat rutin</dt><dd>{{ $person->medications ?: 'Tidak ada data' }}</dd></div>
            </dl>
        </section>

        <section class="m-card ed__card">
            <h2 class="m-card__title">Kontak darurat</h2>
            <dl class="m-dl">
                <div><dt>Nama</dt><dd>{{ $person->emergency_contact_name ?: '–' }}</dd></div>
                <div><dt>Hubungan</dt><dd>{{ $person->emergency_contact_relation ?: '–' }}</dd></div>
                <div><dt>Telepon</dt><dd>@if ($person->emergency_contact_phone)<a href="{{ $phoneLink($person->emergency_contact_phone) }}">{{ $person->emergency_contact_phone }}</a>@else – @endif</dd></div>
                <div><dt>HP pemilik</dt><dd>@if ($person->phone)<a href="{{ $phoneLink($person->phone) }}">{{ $person->phone }}</a>@else – @endif</dd></div>
            </dl>
        </section>

        @if ($climb)
            <section class="m-card ed__card">
                <h2 class="m-card__title">Pendakian tercatat</h2>
                <dl class="m-dl">
                    <div><dt>Gunung</dt><dd>{{ $climb->mountain_name }}</dd></div>
                    <div><dt>Jalur</dt><dd>{{ $climb->route ?: '–' }}</dd></div>
                    <div><dt>Tanggal</dt><dd>{{ $climb->started_on->translatedFormat('d M Y') }}@if ($climb->finished_on) – {{ $climb->finished_on->translatedFormat('d M Y') }}@endif</dd></div>
                    <div><dt>Status</dt><dd>{{ $climb->status->label() }}</dd></div>
                    @if (($companions = $climb->companions())->isNotEmpty())
                        <div><dt>Rombongan</dt><dd>{{ $companions->map->name->implode(', ') }}</dd></div>
                    @endif
                </dl>
            </section>
        @endif

        <section class="m-card ed__card">
            <h2 class="m-card__title">Identitas (KTP)</h2>
            <dl class="m-dl">
                <div><dt>NIK</dt><dd>{{ $person->nik ?: '–' }}</dd></div>
                <div><dt>Nama</dt><dd>{{ $person->name }}</dd></div>
                <div><dt>Tempat/Tgl lahir</dt><dd>{{ collect([$person->birth_place, $person->birth_date?->format('d-m-Y')])->filter()->implode(', ') ?: '–' }}</dd></div>
                <div><dt>Jenis kelamin</dt><dd>{{ $genders[$person->gender] ?? '–' }}</dd></div>
                <div><dt>Alamat</dt><dd>{{ $person->id_card_address ?: '–' }}</dd></div>
                <div><dt>Agama</dt><dd>{{ $person->religion ?: '–' }}</dd></div>
                <div><dt>Status perkawinan</dt><dd>{{ $person->marital_status ?: '–' }}</dd></div>
                <div><dt>Pekerjaan</dt><dd>{{ $person->occupation ?: '–' }}</dd></div>
                <div><dt>Kewarganegaraan</dt><dd>{{ $person->nationality ?: '–' }}</dd></div>
                <div><dt>Domisili</dt><dd>{{ collect([$person->address, $person->city])->filter()->implode(', ') ?: '–' }}</dd></div>
            </dl>
        </section>

        <p class="ed__foot">Halaman ini dibuat untuk membantu penolong dan tim SAR. Mohon tidak menyebarkan data pribadi ini.</p>
    </main>
</body>
</html>
