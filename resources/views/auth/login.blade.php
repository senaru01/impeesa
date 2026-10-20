<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Impeesa X-tream Adventure Team</title>
    <meta name="robots" content="noindex">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/member.css') }}">
</head>
<body class="member">
    <div class="m-login">
        <div class="m-login__media" style="--login-bg: url('{{ asset('images/gunung/segara-anak.jpg') }}')">
            <a href="{{ route('landing') }}" class="m-brand" style="color: var(--m-text); width: fit-content"><span class="m-brand__dot"></span>Impeesa</a>
            <div class="m-login__quote">
                <h2>Setiap puncak punya ceritanya sendiri.</h2>
                <p>Masuk ke area member untuk mencatat pendakianmu, menyimpan galeri pribadi dan berbagi cerita perjalanan bersama tim.</p>
            </div>
        </div>

        <div class="m-login__panel">
            <div class="m-login__box">
                <p class="m-crumb">Impeesa X-tream Adventure Team</p>
                <h1 class="m-title">Masuk member</h1>
                <p class="m-subtitle">Akun dibuat oleh super admin tim.</p>

                <form method="POST" action="{{ route('login') }}" class="m-form" style="margin-top: 28px">
                    @csrf

                    <div class="m-field">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" class="m-input" value="{{ old('email') }}" required autofocus autocomplete="username">
                        @error('email')
                            <span class="m-field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="m-field">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" class="m-input" required autocomplete="current-password">
                        @error('password')
                            <span class="m-field__error">{{ $message }}</span>
                        @enderror
                    </div>

                    <label class="m-check">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                        Ingat saya
                    </label>

                    <button type="submit" class="m-btn m-btn--primary m-btn--block">Masuk</button>
                </form>

                <a href="{{ route('landing') }}" class="m-login__back">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                    Kembali ke beranda
                </a>
            </div>
        </div>
    </div>
</body>
</html>
