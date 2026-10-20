@if ($story->isFeatured())
    <a href="{{ route('stories.public', $story) }}" class="m-btn m-btn--ghost m-btn--sm" target="_blank" rel="noopener">Lihat</a>
    <form method="POST" action="{{ route('admin.stories.unfeature', $story) }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="m-btn m-btn--danger m-btn--sm">Turunkan dari beranda</button>
    </form>
@elseif ($story->is_public)
    <a href="{{ route('stories.public', $story) }}" class="m-btn m-btn--ghost m-btn--sm">Baca</a>
    <form method="POST" action="{{ route('admin.stories.feature', $story) }}">
        @csrf
        <button type="submit" class="m-btn m-btn--primary m-btn--sm">Tampilkan di beranda</button>
    </form>
@else
    <a href="{{ route('stories.public', $story) }}" class="m-btn m-btn--ghost m-btn--sm">Baca</a>
    <span class="m-field__hint" style="text-align: right">Privat: pemilik perlu menjadikannya publik dulu.</span>
@endif
