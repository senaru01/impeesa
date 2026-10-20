{{-- Grid foto. $context: 'private' (galeri pribadi, default) atau 'shared' (Galeri Bersama). --}}
@php($context ??= 'private')

<div class="m-gallery">
    @foreach ($photos as $photo)
        <figure class="m-photo" style="margin: 0">
            <a href="{{ route('photos.show', $photo) }}" class="m-photo__img" target="_blank" rel="noopener">
                <img src="{{ route('photos.show', $photo) }}" alt="{{ $photo->caption }}" loading="lazy">
            </a>
            <figcaption class="m-photo__body">
                <div style="min-width: 0">
                    <p class="m-photo__caption">{{ $photo->caption ?: 'Tanpa keterangan' }}</p>
                    <p class="m-photo__date">
                        {{ ($photo->taken_on ?? $photo->created_at)->translatedFormat('d M Y') }}
                        @if ($context === 'shared')
                            &middot; dari {{ ($photo->user ?? $photo->uploader)?->display_name ?? 'member' }}
                        @elseif ($photo->wasUploadedByOther())
                            &middot; dari {{ $photo->uploader?->display_name ?? 'admin' }}
                        @endif
                    </p>
                </div>

                @if ($context === 'shared' && ! $photo->isSharedOnly())
                    @can('unshare', $photo)
                        <form method="POST" action="{{ route('photos.unshare', $photo) }}" onsubmit="return confirm('Tarik foto ini dari Galeri Bersama? Foto tetap ada di galeri pribadi pemiliknya.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="m-btn m-btn--ghost m-btn--sm">Tarik</button>
                        </form>
                    @endcan
                @else
                    @can('delete', $photo)
                        <form method="POST" action="{{ route('photos.destroy', $photo) }}" onsubmit="return confirm('Hapus foto ini?{{ $photo->isShared() ? ' Foto juga hilang dari Galeri Bersama.' : '' }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="m-icon-btn" style="width: 32px; height: 32px; box-shadow: none; color: var(--m-red)" aria-label="Hapus foto">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                            </button>
                        </form>
                    @endcan
                @endif
            </figcaption>

            @isset($coverFor)
                <div class="m-photo__share">
                    @if ($coverFor->cover_photo_id === $photo->id)
                        <span class="m-chip m-chip--solid">Foto utama</span>
                    @else
                        <form method="POST" action="{{ route('climbs.cover', [$coverFor, $photo]) }}" style="flex: 1">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="m-btn m-btn--ghost m-btn--sm m-btn--block">Jadikan foto utama</button>
                        </form>
                    @endif
                </div>
            @endisset

            @if ($context === 'private')
                @can('share', $photo)
                    <div class="m-photo__share">
                        @if (! $photo->isShared())
                            <form method="POST" action="{{ route('photos.share', $photo) }}">
                                @csrf
                                <button type="submit" class="m-btn m-btn--ghost m-btn--sm m-btn--block">
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M15 14.5c3 0 6 1.8 6 5.5"/></svg>
                                    Bagikan ke Galeri Bersama
                                </button>
                            </form>
                        @else
                            <span @class(['m-chip', 'm-chip--accent' => $photo->isPending(), 'm-chip--green' => ! $photo->isPending()])>
                                {{ $photo->isPending() ? 'Menunggu persetujuan' : 'Di Galeri Bersama' }}
                            </span>
                            <form method="POST" action="{{ route('photos.unshare', $photo) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="m-btn m-btn--ghost m-btn--sm">{{ $photo->isPending() ? 'Batal' : 'Jadikan pribadi' }}</button>
                            </form>
                        @endif
                    </div>
                @endcan
            @endif
        </figure>
    @endforeach
</div>
