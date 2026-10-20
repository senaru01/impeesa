(() => {
    const cards = [...document.querySelectorAll('.card')];
    const bgs = [...document.querySelectorAll('.hero__bg-img')];
    const captions = [...document.querySelectorAll('.caption__item')];
    const progress = document.querySelector('.progress');
    const progressFill = document.getElementById('progressFill');
    const desc = document.getElementById('heroDesc');
    const counter = document.getElementById('counterCurrent');
    const fogBurst = document.getElementById('fogBurst');
    const stage = document.getElementById('stage');
    const descriptions = cards.map((_, i) => window.LANDING_DESCRIPTIONS?.[i]);

    const total = cards.length;
    const AUTOPLAY_MS = 5500;
    let active = 0;
    let previousOffsets = cards.map(() => 0);
    let timer = null;

    const wrapOffset = (i) => {
        let o = i - active;
        if (o > total / 2) o -= total;
        if (o < -total / 2) o += total;
        return o;
    };

    const render = (animateFog = true) => {
        cards.forEach((card, i) => {
            const o = wrapOffset(i);
            const jumped = Math.abs(o - previousOffsets[i]) > 2;

            // Cards that wrap around teleport instead of flying across the stage.
            if (jumped) card.style.transition = 'none';
            card.style.setProperty('--o', o);
            card.style.setProperty('--a', Math.abs(o));
            card.classList.toggle('is-active', o === 0);
            card.classList.toggle('is-hidden', Math.abs(o) > 2);
            card.setAttribute('tabindex', o === 0 && !card.dataset.href ? '-1' : '0');

            // Load card images only once they come near the visible range.
            const img = card.querySelector('img[data-src]');
            if (img && Math.abs(o) <= 3) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            }
            if (jumped) {
                card.getBoundingClientRect();
                card.style.transition = '';
            }
            previousOffsets[i] = o;
        });

        bgs.forEach((bg, i) => {
            if (i === active && !bg.style.backgroundImage) {
                bg.style.backgroundImage = `url('${bg.dataset.bg}')`;
            }
            bg.classList.toggle('is-active', i === active);
        });
        captions.forEach((c, i) => c.classList.toggle('is-active', i === active));
        progressFill.style.width = `${((active + 1) / total) * 100}%`;
        progress.setAttribute('aria-valuenow', active + 1);
        counter.textContent = String(active + 1).padStart(2, '0');

        desc.classList.add('is-fading');
        setTimeout(() => {
            desc.textContent = descriptions[active] ?? desc.textContent;
            desc.classList.remove('is-fading');
        }, 400);

        if (animateFog) {
            fogBurst.classList.remove('is-playing');
            void fogBurst.offsetWidth;
            fogBurst.classList.add('is-playing');
        }
    };

    const goTo = (index) => {
        active = (index + total) % total;
        render();
        restart();
    };

    const restart = () => {
        clearInterval(timer);
        timer = setInterval(() => goTo(active + 1), AUTOPLAY_MS);
    };

    // Cards with a detail page (e.g. Rinjani) open it; the rest just slide into the centre.
    cards.forEach((card, i) => card.addEventListener('click', () => {
        if (card.dataset.href) {
            window.location.href = card.dataset.href;
        } else if (i !== active) {
            goTo(i);
        }
    }));
    document.getElementById('prev').addEventListener('click', () => goTo(active - 1));
    document.getElementById('next').addEventListener('click', () => goTo(active + 1));

    const section = document.getElementById('destinasi');
    document.addEventListener('keydown', (e) => {
        // Only react while the carousel section is on screen, so other sliders keep their own keys.
        const r = section.getBoundingClientRect();
        if (r.top > innerHeight / 2 || r.bottom < innerHeight / 2 || document.querySelector('dialog[open]')) return;
        if (e.key === 'ArrowLeft') goTo(active - 1);
        if (e.key === 'ArrowRight') goTo(active + 1);
    });

    stage.addEventListener('mouseenter', () => clearInterval(timer));
    stage.addEventListener('mouseleave', restart);

    // Swipe on touch devices
    let startX = null;
    stage.addEventListener('touchstart', (e) => { startX = e.touches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', (e) => {
        if (startX === null) return;
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) goTo(active + (dx < 0 ? 1 : -1));
        startX = null;
    });

    document.addEventListener('visibilitychange', () => (document.hidden ? clearInterval(timer) : restart()));

    render(false);
    restart();
})();
