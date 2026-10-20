@extends('layouts.member')

@section('title', 'Pendakian')

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Member &middot; Pendakian</p>
            <h1 class="m-title">Catatan pendakian</h1>
            <p class="m-subtitle">Riwayat dan rencana pendakianmu. Data ini mengisi statistik di dashboard.</p>
        </div>
    </div>

    @include('member.climbs.invitations')

    <div class="m-layout" style="margin-top: 20px">
        <div class="m-card">
            @if ($climbs->isEmpty())
                <p class="m-empty">Belum ada catatan. Mulai dengan mencatat pendakian terakhir atau rencana berikutnya.</p>
            @else
                <div class="m-table-wrap">
                    <table class="m-table">
                        <thead>
                            <tr><th>Gunung</th><th>Tanggal</th><th>Ketinggian</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($climbs as $climb)
                                <tr>
                                    <td>
                                        @php($mainPhoto = $climb->mainPhoto())
                                        @php($companions = $climb->companions())
                                        <div class="m-table__person">
                                            @if ($mainPhoto)
                                                <img class="m-climb-thumb" src="{{ route('photos.show', $mainPhoto) }}" alt="Foto utama {{ $climb->mountain_name }}" loading="lazy">
                                            @endif
                                            <div>
                                                <p class="m-list__title">{{ $climb->mountain_name }}</p>
                                                <p class="m-list__meta">
                                                    {{ $climb->route ? 'via '.$climb->route : '' }}
                                                    @if ($climb->photos->isNotEmpty())
                                                        {{ $climb->route ? '·' : '' }} {{ $climb->photos->count() }} foto
                                                    @endif
                                                </p>
                                                @if ($climb->isJoined())
                                                    <p class="m-list__meta">Diajak {{ $climb->joinedFrom->climb->user->display_name }}@if ($companions->count() > 1) &middot; bersama {{ $companions->skip(1)->map->display_name->implode(', ') }}@endif</p>
                                                @elseif ($climb->participants->isNotEmpty())
                                                    <div class="m-companions">
                                                        @foreach ($climb->participants as $participant)
                                                            <span @class([
                                                                'm-companion',
                                                                'is-accepted' => $participant->status === \App\Enums\ParticipantStatus::Accepted,
                                                                'is-declined' => $participant->status === \App\Enums\ParticipantStatus::Declined,
                                                            ]) title="{{ $participant->user->name }}: {{ $participant->status->label() }}">{{ $participant->user->display_name }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td style="white-space: nowrap">
                                        {{ $climb->started_on->translatedFormat('d M Y') }}
                                        @if ($climb->finished_on && ! $climb->finished_on->isSameDay($climb->started_on))
                                            <span class="m-list__meta">– {{ $climb->finished_on->translatedFormat('d M') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $climb->elevation ? number_format($climb->elevation, 0, ',', '.').' mdpl' : '–' }}</td>
                                    <td>
                                        <span @class([
                                            'm-chip',
                                            'm-chip--green' => $climb->status === \App\Enums\ClimbStatus::Summit,
                                            'm-chip--red' => $climb->status === \App\Enums\ClimbStatus::NotSummit,
                                        ])>{{ $climb->status->label() }}</span>
                                    </td>
                                    <td>
                                        <div class="m-table__actions">
                                            <a href="{{ route('climbs.edit', $climb) }}" class="m-btn m-btn--ghost m-btn--sm">Ubah</a>
                                            <form method="POST" action="{{ route('climbs.destroy', $climb) }}" onsubmit="return confirm('Hapus catatan pendakian ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="m-btn m-btn--danger m-btn--sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('climbs.store') }}" enctype="multipart/form-data" class="m-card m-form m-sticky" id="catat">
            @csrf
            <h2 class="m-card__title">Catat pendakian</h2>
            @include('member.partials.errors')
            @include('member.climbs.fields', ['climb' => new \App\Models\Climb])
            <button type="submit" class="m-btn m-btn--primary">Simpan</button>
        </form>
    </div>
@endsection
