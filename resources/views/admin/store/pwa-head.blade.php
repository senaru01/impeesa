{{-- Aplikasi Kasir Impeesa (PWA): hanya menu Toko, scope /admin/toko. --}}
<link rel="manifest" href="{{ asset('toko.webmanifest') }}">
<meta name="theme-color" content="#1e3a8a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Kasir Impeesa">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<script src="{{ asset('js/toko-pwa.js') }}?v={{ filemtime(public_path('js/toko-pwa.js')) }}" defer></script>
