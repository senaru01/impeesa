@php($home = route('landing'))
<header class="nav" id="nav">
    <div class="nav__inner">
        <a href="{{ $home }}#home" class="nav__brand"><span class="nav__dot"></span>Impeesa</a>

        <nav class="nav__links" id="navLinks">
            <a href="{{ $home }}#home" @class(['is-active' => request()->routeIs('landing')])>Beranda</a>
            <a href="{{ $home }}#galeri">Galeri</a>
            <a href="{{ $home }}#destinasi">Destinasi</a>
            <a href="{{ $home }}#recommendations">Rekomendasi</a>
            <a href="{{ $home }}#cerita">Cerita</a>
            <a href="{{ route('profiles.index') }}" @class(['is-active' => request()->routeIs('profiles.index')])>Anggota</a>
            <a href="{{ route('peta') }}" @class(['is-active' => request()->routeIs('peta')])>Peta Gunung</a>
            <a href="#contact">Kontak</a>
        </nav>

        <div class="nav__actions">
            <a href="{{ route('peta') }}#cari" class="nav__icon" aria-label="Cari jalur pendakian">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn--primary btn--sm">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn--primary btn--sm">Login Member</a>
            @endauth
            <button class="nav__toggle" id="navToggle" type="button" aria-label="Buka menu" aria-expanded="false">
                <span></span><span></span>
            </button>
        </div>
    </div>
</header>
