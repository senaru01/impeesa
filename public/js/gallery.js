(() => {
    const section = document.getElementById('galeri');
    if (!section) return;

    const stage = document.getElementById('galleryStage');
    const slides = [...stage.querySelectorAll('.slide')];
    const thumbs = [...document.querySelectorAll('.filmstrip__item')];
    const strip = document.getElementById('filmstrip');
    const current = document.getElementById('galleryCurrent');
    const playBtn = document.getElementById('galleryPlay');
    const lightbox = document.getElementById('lightbox');

    const total = slides.length;
    const DURATION = 6000;
    let active = 0;
    let playing = true;
    let busy = false;
    let hovering = false;
    let visible = false;

    const pad = (n) => String(n).padStart(2, '0');
    const wrap = (i) => (i + total) % total;

    const load = (i) => {
        const img = slides[wrap(i)].querySelector('img[data-src]');
        if (img) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        }
    };

    // ---------------- Autoplay (pausable timer) ----------------
    let timer = null;
    let remaining = DURATION;
    let startedAt = 0;

    const shouldRun = () => playing && visible && !hovering && !lightbox.open && !document.hidden;
    const pause = () => {
        if (!timer) return;
        clearTimeout(timer);
        timer = null;
        remaining = Math.max(0, remaining - (Date.now() - startedAt));
    };
    const resume = () => {
        if (timer || !shouldRun()) return;
        startedAt = Date.now();
        timer = setTimeout(() => {
            timer = null;
            go(active + 1, 1);
        }, remaining);
    };
    const restartTimer = () => {
        pause();
        remaining = DURATION;
        resume();
    };
    const sync = () => (shouldRun() ? resume() : pause());

    const setPlaying = (on) => {
        playing = on;
        section.classList.toggle('is-paused', !on);
        playBtn.setAttribute('aria-pressed', String(on));
        playBtn.setAttribute('aria-label', on ? 'Jeda slideshow' : 'Putar slideshow');
        sync();
    };

    // ---------------- Slide change: directional wipe ----------------
    const go = (index, dir) => {
        index = wrap(index);
        if (index === active || busy) return;
        dir = dir || (index > active ? 1 : -1);
        busy = true;

        const from = slides[active];
        const to = slides[index];
        load(index);
        load(index + 1);

        slides.forEach((s) => s.classList.remove('is-leaving'));
        from.classList.remove('is-active');
        from.classList.add('is-leaving');

        to.classList.add('is-entering');
        to.style.setProperty('--from', dir > 0 ? 'inset(0 0 0 100%)' : 'inset(0 100% 0 0)');
        void to.offsetWidth;
        to.classList.remove('is-entering');
        to.classList.add('is-active');

        active = index;
        current.textContent = pad(active + 1);
        thumbs.forEach((t, i) => {
            const on = i === active;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', String(on));
        });
        const thumb = thumbs[active];
        strip.scrollTo({ left: thumb.offsetLeft - strip.clientWidth / 2 + thumb.clientWidth / 2, behavior: 'smooth' });

        restartTimer();
        if (lightbox.open) fillLightbox();
        setTimeout(() => {
            from.classList.remove('is-leaving');
            busy = false;
        }, 1000);
    };

    document.getElementById('galleryPrev').addEventListener('click', () => go(active - 1, -1));
    document.getElementById('galleryNext').addEventListener('click', () => go(active + 1, 1));
    playBtn.addEventListener('click', () => setPlaying(!playing));
    thumbs.forEach((t, i) => t.addEventListener('click', () => go(i)));

    stage.addEventListener('mouseenter', () => { hovering = true; sync(); });
    stage.addEventListener('mouseleave', () => {
        hovering = false;
        sync();
        stage.style.setProperty('--mx', 0);
        stage.style.setProperty('--my', 0);
    });

    // Subtle parallax following the cursor
    stage.addEventListener('mousemove', (e) => {
        const r = stage.getBoundingClientRect();
        stage.style.setProperty('--mx', ((e.clientX - r.left) / r.width - 0.5).toFixed(3));
        stage.style.setProperty('--my', ((e.clientY - r.top) / r.height - 0.5).toFixed(3));
    });

    // ---------------- Swipe / drag ----------------
    let startX = null;
    let moved = false;
    stage.addEventListener('pointerdown', (e) => {
        if (e.target.closest('button')) return;
        startX = e.clientX;
        moved = false;
    });
    stage.addEventListener('pointermove', (e) => {
        if (startX !== null && Math.abs(e.clientX - startX) > 10) moved = true;
    });
    stage.addEventListener('pointerup', (e) => {
        if (startX === null) return;
        const dx = e.clientX - startX;
        startX = null;
        if (Math.abs(dx) > 50) go(active + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
        else if (!moved && !e.target.closest('button')) openLightbox();
    });
    stage.addEventListener('pointercancel', () => { startX = null; });

    // ---------------- Lightbox ----------------
    const fillLightbox = () => {
        const slide = slides[active];
        load(active);
        document.getElementById('lightboxImg').src = slide.querySelector('img').src;
        document.getElementById('lightboxImg').alt = slide.querySelector('.slide__title').textContent;
        document.getElementById('lightboxCount').textContent = `${pad(active + 1)} / ${pad(total)}`;
        document.getElementById('lightboxTitle').textContent = slide.querySelector('.slide__title').textContent;
        document.getElementById('lightboxText').textContent = slide.querySelector('.slide__text').textContent;
    };
    const openLightbox = () => {
        if (typeof lightbox.showModal !== 'function') return;
        fillLightbox();
        lightbox.showModal();
        sync();
    };
    document.getElementById('galleryZoom').addEventListener('click', openLightbox);
    lightbox.addEventListener('click', (e) => {
        const action = e.target.closest('[data-lightbox]')?.dataset.lightbox;
        if (action === 'close' || e.target === lightbox) lightbox.close();
        if (action === 'prev') go(active - 1, -1);
        if (action === 'next') go(active + 1, 1);
    });
    lightbox.addEventListener('close', sync);
    let lbStart = null;
    lightbox.addEventListener('touchstart', (e) => { lbStart = e.touches[0].clientX; }, { passive: true });
    lightbox.addEventListener('touchend', (e) => {
        if (lbStart === null) return;
        const dx = e.changedTouches[0].clientX - lbStart;
        if (Math.abs(dx) > 50) go(active + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
        lbStart = null;
    });

    // ---------------- Keyboard (only while the gallery is on screen or in the lightbox) ----------------
    const inView = () => {
        const r = section.getBoundingClientRect();
        return r.top < innerHeight / 2 && r.bottom > innerHeight / 2;
    };
    document.addEventListener('keydown', (e) => {
        if (!lightbox.open && !inView()) return;
        if (e.key === 'ArrowLeft') go(active - 1, -1);
        if (e.key === 'ArrowRight') go(active + 1, 1);
    });

    // Only run the slideshow while it is visible.
    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        sync();
    }, { threshold: 0.25 }).observe(stage);
    document.addEventListener('visibilitychange', sync);

    load(1);
})();
