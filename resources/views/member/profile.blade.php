@extends('layouts.member')

@section('title', 'Data Diri')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Member &middot; Data Diri</p>
            <h1 class="m-title">Data diri</h1>
            <p class="m-subtitle">Lengkapi data diri dan kontak darurat untuk keperluan pendakian bersama tim.</p>
        </div>
    </div>

    <div class="m-layout">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="m-card m-form">
            @csrf
            @method('PUT')
            @include('member.partials.errors')
            @include('member.partials.personal-fields', ['person' => $user])
            <div class="m-actions">
                <button type="submit" class="m-btn m-btn--primary">Simpan data diri</button>
            </div>
        </form>

        <div class="m-sticky" style="display: grid; gap: 20px">
            <div class="m-card">
                <h2 class="m-card__title" style="margin-bottom: 14px">Keanggotaan</h2>
                <dl class="m-dl">
                    <div><dt>NIA</dt><dd>{{ $user->member_number ?: '–' }}</dd></div>
                    <div><dt>Peran</dt><dd>{{ $user->role->label() }}</dd></div>
                    <div><dt>Jabatan</dt><dd>{{ $user->position ?: '–' }}</dd></div>
                    <div><dt>Bergabung</dt><dd>{{ $user->joined_on?->translatedFormat('d F Y') ?? '–' }}</dd></div>
                </dl>
                <p class="m-note">NIA, jabatan dan peran diatur oleh super admin.</p>
            </div>

            <form method="POST" action="{{ route('profile.password') }}" class="m-card m-form">
                @csrf
                @method('PUT')
                <h2 class="m-card__title">Ganti password</h2>
                @include('member.partials.errors', ['bag' => 'password'])
                <div class="m-field">
                    <label for="current_password">Password saat ini</label>
                    <input id="current_password" name="current_password" type="password" class="m-input" required autocomplete="current-password">
                </div>
                <div class="m-field">
                    <label for="new_password">Password baru</label>
                    <input id="new_password" name="password" type="password" class="m-input" required autocomplete="new-password">
                </div>
                <div class="m-field">
                    <label for="password_confirmation">Ulangi password baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="m-input" required autocomplete="new-password">
                </div>
                <button type="submit" class="m-btn m-btn--ghost">Ganti password</button>
            </form>
        </div>
    </div>
@endsection
