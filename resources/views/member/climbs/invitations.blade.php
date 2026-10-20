{{-- Ajakan pendakian yang menunggu jawaban. Parameter: $invitations. --}}
@if ($invitations->isNotEmpty())
    <section class="m-card m-invites" aria-label="Ajakan pendakian">
        <div class="m-card__head">
            <h2 class="m-card__title">Ajakan pendakian</h2>
            <span class="m-chip m-chip--accent">{{ $invitations->count() }} menunggu jawaban</span>
        </div>
        <ul class="m-list">
            @foreach ($invitations as $invitation)
                @php($climb = $invitation->climb)
                @php($others = $climb->participants->where('user_id', '!=', $invitation->user_id))
                <li class="m-invite">
                    <span class="m-list__avatar">{{ $climb->user->initials }}</span>
                    <div class="m-invite__body">
                        <p class="m-list__title">{{ $climb->user->display_name }} mengajakmu ke {{ $climb->mountain_name }}</p>
                        <p class="m-list__meta">
                            {{ $climb->started_on->translatedFormat('d M Y') }}@if ($climb->finished_on && ! $climb->finished_on->isSameDay($climb->started_on)) – {{ $climb->finished_on->translatedFormat('d M Y') }}@endif
                            @if ($climb->route) &middot; via {{ $climb->route }}@endif
                            @if ($others->isNotEmpty()) &middot; juga diajak: {{ $others->map(fn ($participant) => $participant->user->display_name)->implode(', ') }}@endif
                        </p>
                    </div>
                    <div class="m-actions">
                        <form method="POST" action="{{ route('invitations.accept', $invitation) }}">
                            @csrf
                            <button type="submit" class="m-btn m-btn--primary m-btn--sm">Setuju</button>
                        </form>
                        <form method="POST" action="{{ route('invitations.decline', $invitation) }}">
                            @csrf
                            <button type="submit" class="m-btn m-btn--ghost m-btn--sm">Tidak ikut</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
