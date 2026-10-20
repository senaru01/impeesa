@extends('layouts.member')

@section('title', 'Kartu Darurat')

@section('content')
    @php($isOwn = $person->is(auth()->user()))

    <div class="m-pagehead no-print">
        <div>
            <p class="m-crumb">
                @if ($isOwn)
                    Member &middot; Kartu Darurat
                @else
                    <a href="{{ route('admin.members.show', $person) }}">Anggota</a> &middot; Kartu Darurat
                @endif
            </p>
            <h1 class="m-title">Kartu darurat {{ $isOwn ? '' : $person->display_name }}</h1>
            <p class="m-subtitle">Cetak, laminating, lalu simpan di dompet atau tas carrier. Tim SAR cukup memindai QR untuk membaca data penting, bahkan tanpa sinyal.</p>
        </div>
        <div class="m-actions">
            <button type="button" class="m-btn m-btn--primary" onclick="window.print()">Cetak kartu</button>
            <a href="{{ route('emergency.show', $person->emergencyToken()) }}" class="m-btn m-btn--ghost" target="_blank" rel="noopener">Lihat halaman detail</a>
        </div>
    </div>

    @if ($missing)
        <div class="m-errors no-print" role="alert">
            <strong>Data belum lengkap:</strong> {{ implode(', ', $missing) }}.
            @if ($isOwn)
                <a href="{{ route('profile.edit') }}" style="text-decoration: underline">Lengkapi di Data Diri</a>, lalu cetak ulang kartu.
            @else
                <a href="{{ route('admin.members.edit', $person) }}" style="text-decoration: underline">Lengkapi datanya</a>, lalu cetak ulang kartu.
            @endif
        </div>
    @endif

    <div class="ec-sheet">
        {{-- Depan --}}
        <section class="ec-card ec-card--front" aria-label="Kartu darurat bagian depan">
            <header class="ec-card__band">
                <span>DARURAT &middot; EMERGENCY</span>
                <span>Impeesa</span>
            </header>
            <div class="ec-card__front-body">
                <div class="ec-card__info">
                    <p class="ec-card__name">{{ $person->name }}</p>
                    <p class="ec-card__muted">{{ $person->member_number ? 'NIA '.$person->member_number : 'Anggota Impeesa' }}</p>
                    <div class="ec-card__blood">
                        <span>Gol. darah</span>
                        <strong>{{ $person->blood_type ?: '–' }}</strong>
                    </div>
                    <p class="ec-card__label">Kontak darurat</p>
                    <p class="ec-card__value">{{ $person->emergency_contact_name ?: '–' }}@if ($person->emergency_contact_relation) ({{ $person->emergency_contact_relation }})@endif</p>
                    <p class="ec-card__value"><strong>{{ $person->emergency_contact_phone ?: '–' }}</strong></p>
                </div>
                <div class="ec-card__qr" id="emergencyQr" data-text="{{ $qrText }}" role="img" aria-label="Kode QR data darurat"></div>
            </div>
        </section>

        {{-- Belakang --}}
        <section class="ec-card ec-card--back" aria-label="Kartu darurat bagian belakang">
            <header class="ec-card__band"><span>UNTUK PENOLONG / TIM SAR</span></header>
            <ol class="ec-card__steps">
                <li>Pindai QR di sisi depan dengan kamera HP. Data medis &amp; kontak darurat langsung terbaca <strong>tanpa internet</strong>.</li>
                <li>Jika ada sinyal, buka tautan di dalam QR untuk data lengkap (KTP, foto, jadwal pendakian).</li>
                <li>Hubungi kontak darurat dan Basarnas <strong>115</strong>.</li>
            </ol>
            <p class="ec-card__en">Scan the QR code for medical info &amp; emergency contact. Works offline.</p>
            <p class="ec-card__foot">Impeesa X-tream Adventure Team</p>
        </section>
    </div>

    <div class="m-grid no-print" style="margin-top: 24px">
        <article class="m-card m-col-2">
            <h2 class="m-card__title" style="margin-bottom: 10px">Isi QR (terbaca tanpa sinyal)</h2>
            <pre class="ec-preview">{{ $qrText }}</pre>
            <p class="m-note">Data KTP lengkap tidak dimasukkan ke QR. Data itu hanya ada di halaman detail yang dibuka lewat tautan di baris terakhir.</p>
        </article>
        <article class="m-card m-col-2">
            <h2 class="m-card__title" style="margin-bottom: 10px">Keamanan</h2>
            <p style="line-height: 1.7; color: #475569">Siapa pun yang memindai QR bisa membuka halaman detail, termasuk data KTP. Jika kartu hilang, buat kode QR baru supaya tautan lama tidak berlaku, lalu cetak ulang kartu.</p>
            <p class="m-note">QR menyimpan data saat dicetak. Setiap kali data diri berubah, cetak ulang kartunya.</p>
            @if ($isOwn)
                <form method="POST" action="{{ route('emergency.regenerate') }}" onsubmit="return confirm('Buat kode QR baru? Kartu lama tidak bisa membuka halaman detail lagi.')" style="margin-top: 14px">
                    @csrf
                    <button type="submit" class="m-btn m-btn--danger">Buat kode QR baru</button>
                </form>
            @endif
        </article>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
    <script>
        (() => {
            const target = document.getElementById('emergencyQr');
            qrcode.stringToBytes = qrcode.stringToBytesFuncs['UTF-8'];
            const qr = qrcode(0, 'M');
            qr.addData(target.dataset.text, 'Byte');
            qr.make();
            target.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
        })();
    </script>
@endsection
