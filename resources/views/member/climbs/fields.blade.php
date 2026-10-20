<div class="m-field">
    <label for="mountain_name">Gunung</label>
    <input id="mountain_name" name="mountain_name" class="m-input" list="mountain-options" value="{{ old('mountain_name', $climb->mountain_name) }}" required>
    <datalist id="mountain-options">
        @foreach ($mountains as $name => $elevation)
            <option value="{{ $name }}" data-elevation="{{ $elevation }}"></option>
        @endforeach
    </datalist>
</div>
<div class="m-form__grid">
    <div class="m-field">
        <label for="route">Jalur</label>
        <input id="route" name="route" class="m-input" value="{{ old('route', $climb->route) }}" placeholder="mis. Sembalun">
    </div>
    <div class="m-field">
        <label for="elevation">Ketinggian (mdpl)</label>
        <input id="elevation" name="elevation" type="number" min="0" max="9000" class="m-input" value="{{ old('elevation', $climb->elevation) }}">
    </div>
    <div class="m-field">
        <label for="started_on">Tanggal naik</label>
        <input id="started_on" name="started_on" type="date" class="m-input" value="{{ old('started_on', $climb->started_on?->format('Y-m-d')) }}" required>
    </div>
    <div class="m-field">
        <label for="finished_on">Tanggal turun</label>
        <input id="finished_on" name="finished_on" type="date" class="m-input" value="{{ old('finished_on', $climb->finished_on?->format('Y-m-d')) }}">
    </div>
</div>
<div class="m-field">
    <label for="status">Status</label>
    <select id="status" name="status" class="m-input">
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(old('status', $climb->status?->value ?? 'planned') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
</div>
@if ($climb->exists && $climb->isJoined())
    <div class="m-field">
        <span class="m-field__label">Bersama siapa?</span>
        <p class="m-field__hint">Kamu ikut pendakian {{ $climb->joinedFrom->climb->user->display_name }}. Gunung, jalur dan tanggal mengikuti perubahan dari pengajak; status dan catatan tetap milikmu.</p>
    </div>
@else
    @php($selectedParticipants = collect(old('participants', $climb->participants->pluck('user_id')->all()))->map(fn ($id) => (int) $id))
    @php($participantStatuses = $climb->participants->keyBy('user_id'))
    <div class="m-field" data-member-picker>
        <span class="m-field__label">Bersama siapa?</span>
        <input type="search" class="m-input" placeholder="Cari anggota..." aria-label="Cari anggota" data-member-search>
        <div class="m-members">
            @forelse ($members as $member)
                <label class="m-member" data-search="{{ \Illuminate\Support\Str::lower($member->name.' '.$member->nickname.' '.$member->member_number) }}">
                    <input type="checkbox" name="participants[]" value="{{ $member->id }}" @checked($selectedParticipants->contains($member->id))>
                    <span class="m-list__avatar">{{ $member->initials }}</span>
                    <span class="m-member__name">
                        <strong>{{ $member->name }}</strong>
                        <span>{{ $member->member_number ? 'NIA '.$member->member_number : ($member->nickname ?: 'Anggota') }}</span>
                    </span>
                    @if ($status = $participantStatuses->get($member->id)?->status)
                        <span @class([
                            'm-chip',
                            'm-chip--green' => $status === \App\Enums\ParticipantStatus::Accepted,
                            'm-chip--red' => $status === \App\Enums\ParticipantStatus::Declined,
                            'm-chip--grey' => $status === \App\Enums\ParticipantStatus::Pending,
                        ])>{{ $status->label() }}</span>
                    @endif
                </label>
            @empty
                <p class="m-empty" style="padding: 10px">Belum ada anggota lain.</p>
            @endforelse
        </div>
        <span class="m-field__hint">Anggota yang dicentang mendapat ajakan dan bisa memilih setuju atau tidak. Yang setuju otomatis mendapat catatan pendakian yang sama.</span>
    </div>
@endif
<div class="m-field">
    <label for="climb_photos">{{ $climb->exists ? 'Tambah foto' : 'Foto pendakian' }}</label>
    <input id="climb_photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="m-input" data-photo-picker="cover" data-max="10">
    <span class="m-field__hint">Opsional, maks. 10 foto; bisa dipilih bertahap. {{ $climb->exists && $climb->cover_photo_id ? 'Foto utama bisa diganti dari daftar foto di samping.' : 'Foto bertanda "Utama" tampil di data pendakian dan peta.' }} Semua foto masuk ke galeri pribadimu.</span>
</div>
<div class="m-field">
    <label for="notes">Catatan</label>
    <textarea id="notes" name="notes" class="m-input" rows="3" placeholder="Cuaca, kondisi jalur, logistik...">{{ old('notes', $climb->notes) }}</textarea>
</div>

<script>
    // Isi ketinggian otomatis ketika nama gunung dipilih dari daftar.
    document.getElementById('mountain_name').addEventListener('change', (e) => {
        const option = [...document.querySelectorAll('#mountain-options option')].find((o) => o.value === e.target.value);
        const elevation = document.getElementById('elevation');
        if (option && !elevation.value) elevation.value = option.dataset.elevation;
    });

    // Saring daftar anggota berdasarkan nama, panggilan atau NIA.
    document.querySelector('[data-member-search]')?.addEventListener('input', (e) => {
        const query = e.target.value.trim().toLowerCase();
        document.querySelectorAll('.m-member').forEach((item) => {
            item.hidden = query !== '' && !item.dataset.search.includes(query);
        });
    });
</script>
