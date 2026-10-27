<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Impeesa</title>
    <meta name="robots" content="noindex">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet">
    @stack('head')
    <link rel="stylesheet" href="{{ asset('css/member.css') }}">
</head>
<body class="member">
    @php($authUser = auth()->user())

    <header class="m-header">
        <div class="m-wrap">
            <div class="m-header__top">
                <a href="{{ route('landing') }}" class="m-brand"><span class="m-brand__dot"></span>Impeesa</a>

                <div class="m-header__actions">
                    <span class="m-role">{{ $authUser->role->label() }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="m-icon-btn" aria-label="Keluar" title="Keluar">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M10 16l-4-4 4-4M6 12h10"/></svg>
                        </button>
                    </form>
                    <a href="{{ route('profile.edit') }}" class="m-avatar" aria-label="Profil saya">
                        @if ($authUser->avatar_path)
                            <img src="{{ route('avatar', $authUser) }}" alt="">
                        @else
                            {{ $authUser->initials }}
                        @endif
                    </a>
                </div>
            </div>

            <nav class="m-tabs" aria-label="Menu member">
                <a href="{{ route('dashboard') }}" @class(['is-active' => request()->routeIs('dashboard')])>Dashboard</a>
                <a href="{{ route('climbs.index') }}" @class(['is-active' => request()->routeIs('climbs.*')])>Pendakian</a>
                <a href="{{ route('photos.index') }}" @class(['is-active' => request()->routeIs('photos.index')])>Galeri Pribadi</a>
                <a href="{{ route('photos.shared') }}" @class(['is-active' => request()->routeIs('photos.shared')])>Galeri Bersama</a>
                <a href="{{ route('stories.index') }}" @class(['is-active' => request()->routeIs('stories.*') && ! request()->routeIs('stories.shared')])>Cerita Saya</a>
                <a href="{{ route('stories.shared') }}" @class(['is-active' => request()->routeIs('stories.shared')])>Cerita Bersama</a>
                <a href="{{ route('health.index') }}" @class(['is-active' => request()->routeIs('health.*')])>Kesehatan</a>
                <a href="{{ route('profile.edit') }}" @class(['is-active' => request()->routeIs('profile.*')])>Data Diri</a>
                <a href="{{ route('emergency.card') }}" @class(['is-active' => request()->routeIs('emergency.card')])>Kartu Darurat</a>
                @can('admin')
                    <span class="m-tabs__sep" aria-hidden="true"></span>
                    <a href="{{ route('admin.members.index') }}" @class(['is-active' => request()->routeIs('admin.members.*')])>Anggota</a>
                    <a href="{{ route('admin.stories.index') }}" @class(['is-active' => request()->routeIs('admin.stories.*')])>Kelola Cerita</a>
                @endcan
            </nav>
        </div>
    </header>

    <main class="m-main">
        <div class="m-wrap">
            @if (session('status'))
                <p class="m-flash" role="status">{{ session('status') }}</p>
            @endif

            @yield('content')
        </div>
    </main>

    <script src="{{ asset('js/photo-picker.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
