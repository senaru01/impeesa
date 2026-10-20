@extends('layouts.member')

@section('title', $member->exists ? 'Ubah Anggota' : 'Tambah Anggota')

@section('content')
    @php($isSelf = $member->exists && $member->is(auth()->user()))

    <div class="m-pagehead">
        <div>
            <p class="m-crumb"><a href="{{ route('admin.members.index') }}">Anggota</a> &middot; {{ $member->exists ? 'Ubah' : 'Baru' }}</p>
            <h1 class="m-title">{{ $member->exists ? $member->name : 'Tambah anggota' }}</h1>
        </div>
    </div>

    <form method="POST" action="{{ $member->exists ? route('admin.members.update', $member) : route('admin.members.store') }}" enctype="multipart/form-data" class="m-layout">
        @csrf
        @if ($member->exists)
            @method('PUT')
        @endif

        <div class="m-card m-form">
            @include('member.partials.errors')
            @include('member.partials.personal-fields', ['person' => $member])
        </div>

        <div class="m-sticky" style="display: grid; gap: 20px">
            <div class="m-card m-form">
                <h2 class="m-card__title">Keanggotaan & akun</h2>
                <div class="m-field">
                    <label for="member_number">NIA (nomor induk anggota)</label>
                    <input id="member_number" name="member_number" class="m-input" value="{{ old('member_number', $member->member_number) }}">
                </div>
                <div class="m-field">
                    <label for="position">Jabatan</label>
                    <input id="position" name="position" class="m-input" value="{{ old('position', $member->position) }}" placeholder="mis. Ketua, Logistik, Anggota">
                </div>
                <div class="m-field">
                    <label for="joined_on">Tanggal bergabung</label>
                    <input id="joined_on" name="joined_on" type="date" class="m-input" value="{{ old('joined_on', $member->joined_on?->format('Y-m-d')) }}">
                </div>
                <div class="m-field">
                    <label for="role">Peran</label>
                    <select id="role" name="role" class="m-input" @disabled($isSelf)>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $member->role?->value) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    @if ($isSelf)
                        <input type="hidden" name="role" value="{{ $member->role->value }}">
                        <span class="m-field__hint">Kamu tidak bisa mengubah peran akunmu sendiri.</span>
                    @endif
                </div>
                <label class="m-check">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $member->is_active)) @disabled($isSelf)>
                    Akun aktif (boleh login)
                </label>
                @if ($isSelf)
                    <input type="hidden" name="is_active" value="1">
                @endif
            </div>

            <div class="m-card m-form">
                <h2 class="m-card__title">Password</h2>
                <div class="m-field">
                    <label for="password">{{ $member->exists ? 'Password baru' : 'Password' }}</label>
                    <input id="password" name="password" type="password" class="m-input" autocomplete="new-password" @required(! $member->exists)>
                    @if ($member->exists)
                        <span class="m-field__hint">Kosongkan jika tidak ingin mengganti.</span>
                    @endif
                </div>
                <div class="m-field">
                    <label for="password_confirmation">Ulangi password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="m-input" autocomplete="new-password">
                </div>
            </div>

            <div class="m-actions">
                <button type="submit" class="m-btn m-btn--primary">{{ $member->exists ? 'Simpan perubahan' : 'Buat akun' }}</button>
                <a href="{{ $member->exists ? route('admin.members.show', $member) : route('admin.members.index') }}" class="m-btn m-btn--ghost">Batal</a>
            </div>
        </div>
    </form>
@endsection
