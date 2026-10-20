<footer class="footer" id="contact">
    <div class="container footer__inner">
        <div>
            <a href="{{ route('landing') }}#home" class="nav__brand"><span class="nav__dot"></span>Impeesa</a>
            <p class="footer__note">Petualangan tak pernah jauh. Jelajahi keindahan gunung-gunung Indonesia bersama kami.</p>
        </div>
        <div class="footer__cta">
            <p>Anggota tim Impeesa?</p>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn--primary">Buka Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn--primary">Login Member</a>
            @endauth
        </div>
    </div>
    <div class="container footer__bottom">
        <span>&copy; {{ date('Y') }} Impeesa. Hak cipta dilindungi.</span>
        @yield('footer-credits')
    </div>
</footer>
