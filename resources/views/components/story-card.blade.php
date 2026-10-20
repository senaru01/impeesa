@props(['story', 'showAuthor' => false])

<article class="m-card m-story">
    <div class="m-story__cover">
        @if ($story->cover_path)
            <img src="{{ route('stories.cover', $story) }}" alt="" loading="lazy">
        @endif
    </div>
    <div class="m-story__body">
        <div class="m-actions">
            @if ($story->is_public)
                <span class="m-chip m-chip--green">Publik</span>
            @else
                <span class="m-chip m-chip--grey">
                    <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                    Privat
                </span>
            @endif
            @if ($story->isFeatured())
                <span class="m-chip m-chip--accent">Tampil di beranda</span>
            @endif
        </div>
        <h3 class="m-story__title">{{ $story->title }}</h3>
        <p class="m-story__meta">
            @if ($showAuthor){{ $story->user->name }} &middot; @endif
            {{ $story->mountain_name ?: 'Cerita pendakian' }}@if ($story->climbed_on) &middot; {{ $story->climbed_on->translatedFormat('M Y') }}@endif
        </p>
        <p class="m-story__excerpt">{{ \Illuminate\Support\Str::limit($story->body, 160) }}</p>
        <div class="m-story__foot">
            {{ $slot }}
        </div>
    </div>
</article>
