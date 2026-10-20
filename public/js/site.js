(() => {
    // Navbar: background on scroll + mobile toggle
    const nav = document.getElementById('nav');
    const toggle = document.getElementById('navToggle');
    const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 40);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    toggle.addEventListener('click', () => {
        const open = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(open));
    });
    document.querySelectorAll('#navLinks a').forEach((link) =>
        link.addEventListener('click', () => {
            nav.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        })
    );

    // Reveal on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

    // Morph: name the clicked teaser so the browser morphs it into the next page's hero.
    document.querySelectorAll('[data-morph]').forEach((link) =>
        link.addEventListener('click', () => link.classList.add('is-morphing'))
    );
    window.addEventListener('pageshow', () =>
        document.querySelectorAll('.is-morphing').forEach((el) => el.classList.remove('is-morphing'))
    );
})();
