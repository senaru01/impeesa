<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Impeesa X-tream Adventure Team')</title>
    <meta name="description" content="@yield('description', 'Impeesa X-tream Adventure Team — menjelajahi gunung-gunung terindah di Indonesia, dari Kerinci hingga Kelimutu.')">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=montserrat:500,600,700,800|open-sans:400,600" rel="stylesheet">
    @stack('head')
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
</head>
<body class="@yield('body-class')">

    @include('partials.nav')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <script src="{{ asset('js/site.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
