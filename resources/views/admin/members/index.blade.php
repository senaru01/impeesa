@extends('layouts.member')

@section('title', 'Anggota')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Super Admin &middot; Anggota</p>
            <h1 class="m-title">Anggota tim</h1>
            <p class="m-subtitle">{{ $members->total() }} akun terdaftar.</p>
        </div>
        <a href="{{ route('admin.members.create') }}" class="m-btn m-btn--primary">Tambah anggota</a>
    </div>

    <div class="m-card">
        <form method="GET" class="m-actions" style="margin-bottom: 16px">
            <input type="search" name="q" value="{{ $search }}" class="m-input" placeholder="Cari nama, email atau NIA..." style="max-width: 360px">
            <button type="submit" class="m-btn m-btn--ghost">Cari</button>
        </form>

        <div class="m-table-wrap">
            <table class="m-table">
                <thead>
                    <tr><th>Anggota</th><th>NIA</th><th>Jabatan</th><th>Pendakian</th><th>Foto</th><th>Cerita</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td>
                                <div class="m-table__person">
                                    <span class="m-list__avatar">
                                        @if ($member->avatar_path)
                                            <img src="{{ route('avatar', $member) }}" alt="">
                                        @else
                                            {{ $member->initials }}
                                        @endif
                                    </span>
                                    <div>
                                        <p class="m-list__title">{{ $member->name }}</p>
                                        <p class="m-list__meta">{{ $member->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $member->member_number ?: '–' }}</td>
                            <td>{{ $member->position ?: $member->role->label() }}</td>
                            <td>{{ $member->climbs_count }}</td>
                            <td>{{ $member->photos_count }}</td>
                            <td>{{ $member->stories_count }}</td>
                            <td>
                                <span @class(['m-chip', 'm-chip--green' => $member->is_active, 'm-chip--grey' => ! $member->is_active])>{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                @if ($member->isProfileFeatured())
                                    <span class="m-chip m-chip--accent">Profil publik</span>
                                @endif
                            </td>
                            <td>
                                <div class="m-table__actions">
                                    <a href="{{ route('admin.members.show', $member) }}" class="m-btn m-btn--ghost m-btn--sm">Detail</a>
                                    <a href="{{ route('admin.members.edit', $member) }}" class="m-btn m-btn--ghost m-btn--sm">Ubah</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="m-empty">Tidak ada anggota yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="m-pagination">{{ $members->links('pagination::simple-bootstrap-5') }}</div>
    </div>
@endsection
