// Jelajah Galeri Bersama: slideshow, peta jejak, dan filmstrip yang saling tersinkron.
(() => {
    const root = document.getElementById('galleryExplorer');
    const data = window.GALLERY_EXPLORE;
    if (!root || !data || !data.slides.length) return;

    const { slides, places } = data;
    const DURATION = 5500;
    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const $ = (name) => root.querySelector(`[data-explore="${name}"]`);
    const escape = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

    const stage = root.querySelector('.m-explore__stage');
    const layers = [...stage.querySelectorAll('.m-explore__slide')];
    const progress = stage.querySelector('.m-explore__progress span');
    const strip = $('strip');
    const where = $('where');
    const playBtn = $('play');
    const placeSelect = root.querySelector('[data-explore-filter="place"]');
    const memberSelect = root.querySelector('[data-explore-filter="member"]');
    const lightbox = document.getElementById('exploreLightbox');

    const filter = { place: '', member: '' };
    let list = slides.map((_, i) => i); // indeks slide yang lolos filter
    let trail = []; // indeks lokasi sesuai urutan kunjungan pertama dalam list
    let pos = 0;
    let front = 0;
    let lastPlace = undefined;
    let renderToken = 0;

    // ---------------- Peta ----------------
    let map = null;
    let markers = [];
    let trailLine = null;
    let visitedLine = null;

    if (window.L) {
        map = L.map('exploreMap', { zoomControl: false, scrollWheelZoom: false }).setView([-2.5, 117.5], 4);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        const esri = (service, label) => L.tileLayer(`https://server.arcgisonline.com/ArcGIS/rest/services/${service}/MapServer/tile/{z}/{y}/{x}`, {
            maxZoom: 17,
            attribution: `Tiles &copy; Esri — ${label}`,
        });
        const baseLayers = {
            Terang: esri('Canvas/World_Light_Gray_Base', 'Esri, HERE, Garmin, &copy; OpenStreetMap contributors'),
            Satelit: esri('World_Imagery', 'Esri, Maxar, Earthstar Geographics'),
            Topografi: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                maxZoom: 17,
                attribution: 'Map &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA), &copy; OpenStreetMap contributors',
            }),
        };
        baseLayers.Satelit.addTo(map);
        L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);
        map.once('click focus', () => map.scrollWheelZoom.enable());

        trailLine = L.polyline([], { color: '#ffffff', weight: 3, opacity: 0.75, dashArray: '6 8', interactive: false }).addTo(map);
        visitedLine = L.polyline([], { color: '#f26b1d', weight: 4, opacity: 0.9, interactive: false }).addTo(map);

        markers = places.map((place, i) => L.marker([place.lat, place.lng], {
            title: place.name,
            riseOnHover: true,
            icon: L.divIcon({ className: 'm-xpin', html: `<span>${place.count}</span>`, iconSize: [30, 30], iconAnchor: [15, 15] }),
        })
            .bindTooltip(`<strong>${escape(place.name)}</strong><br>${place.count} foto · klik untuk melihat`, { direction: 'top', offset: [0, -14] })
            .on('click', () => setFilter({ place: String(i) }))
            .addTo(map));
    }

    const fitTrail = () => {
        if (!map) return;
        const points = trail.map((i) => [places[i].lat, places[i].lng]);
        if (points.length === 1) {
            map.setView(points[0], 9, { animate: !reduceMotion });
        } else if (points.length > 1) {
            map.fitBounds(points, { padding: [40, 40], maxZoom: 9, animate: !reduceMotion });
        }
    };

    const updateMap = (place) => {
        const step = place === null ? -1 : trail.indexOf(place);

        markers.forEach((marker, i) => {
            const el = marker.getElement();
            const order = trail.indexOf(i);
            el?.classList.toggle('is-active', i === place);
            el?.classList.toggle('is-visited', order !== -1 && order < step);
            el?.classList.toggle('is-dim', order === -1);
            marker.setZIndexOffset(i === place ? 1000 : 0);
        });

        if (!map) return;
        visitedLine.setLatLngs(step < 0 ? [] : trail.slice(0, step + 1).map((i) => [places[i].lat, places[i].lng]));

        if (place !== null && place !== lastPlace) {
            const target = [places[place].lat, places[place].lng];
            const zoom = Math.min(Math.max(map.getZoom(), 6), 10);
            reduceMotion ? map.setView(target, zoom) : map.flyTo(target, zoom, { duration: 1.1 });
        }
    };

    const updateWhere = (place) => {
        const filtered = filter.place !== '';
        where.innerHTML = (place === null
            ? '<span>Lokasi foto ini belum diketahui</span>'
            : `<span><b>${escape(places[place].name)}</b> · ${places[place].count} foto</span>`)
            + (filtered ? '<button type="button" data-explore-reset>Semua lokasi</button>' : '');
    };
    where.addEventListener('click', (e) => {
        if (e.target.closest('[data-explore-reset]')) setFilter({ place: '' });
    });

    // ---------------- Filmstrip ----------------
    const buildStrip = () => {
        strip.innerHTML = list.map((index, i) => `
            <button type="button" role="tab" data-pos="${i}" aria-label="Foto ${i + 1}: ${escape(slides[index].caption)}">
                <img src="${escape(slides[index].url)}" alt="" loading="lazy">
            </button>`).join('');
    };
    strip.addEventListener('click', (e) => {
        const thumb = e.target.closest('[data-pos]');
        if (thumb) go(Number(thumb.dataset.pos));
    });

    const syncStrip = () => {
        strip.querySelectorAll('[data-pos]').forEach((thumb, i) => {
            const active = i === pos;
            thumb.classList.toggle('is-active', active);
            thumb.setAttribute('aria-selected', String(active));
            if (active) {
                strip.scrollTo({ left: thumb.offsetLeft - (strip.clientWidth - thumb.offsetWidth) / 2, behavior: reduceMotion ? 'auto' : 'smooth' });
            }
        });
    };

    // ---------------- Slideshow ----------------
    const metaText = (slide) => [
        slide.place === null ? null : places[slide.place].name,
        slide.date,
        `dari ${slide.by}`,
    ].filter(Boolean).join(' · ');

    const preload = (offset) => {
        if (list.length < 2) return;
        new Image().src = slides[list[(pos + offset + list.length) % list.length]].url;
    };

    const render = () => {
        const slide = slides[list[pos]];
        const token = ++renderToken;
        const back = layers[1 - front];
        const [bg, img] = back.querySelectorAll('img');

        const swap = () => {
            if (token !== renderToken) return;
            layers[front].classList.remove('is-active');
            back.classList.add('is-active');
            front = 1 - front;
        };

        bg.src = slide.url;
        img.src = slide.url;
        img.alt = slide.caption;
        img.complete ? swap() : (img.onload = img.onerror = swap);

        $('caption').textContent = slide.caption;
        $('meta').textContent = metaText(slide);
        $('counter').textContent = `${pos + 1} / ${list.length}`;
        stage.classList.toggle('is-single', list.length < 2);

        syncStrip();
        updateWhere(slide.place);
        updateMap(slide.place);
        lastPlace = slide.place;
        preload(1);
        preload(-1);
        if (lightbox.open) showLightbox();
        restartTimer();
    };

    const go = (target) => {
        pos = (target + list.length) % list.length;
        render();
    };
    const step = (delta) => go(pos + delta);

    // ---------------- Autoplay (animasi progress = timer) ----------------
    let playing = !reduceMotion;
    let hovering = false;
    let visible = false;

    const shouldRun = () => playing && visible && !hovering && list.length > 1 && !lightbox.open && !document.hidden;
    const sync = () => stage.classList.toggle('is-running', shouldRun());
    const restartTimer = () => {
        progress.classList.remove('is-animating');
        void progress.offsetWidth;
        if (playing && list.length > 1) progress.classList.add('is-animating');
        sync();
    };
    progress.style.animationDuration = `${DURATION}ms`;
    progress.addEventListener('animationend', () => step(1));

    const setPlaying = (on) => {
        playing = on;
        playBtn.setAttribute('aria-pressed', String(on));
        playBtn.setAttribute('aria-label', on ? 'Jeda slideshow' : 'Putar slideshow');
        root.classList.toggle('is-paused', !on);
        restartTimer();
    };
    setPlaying(playing);

    playBtn.addEventListener('click', () => setPlaying(!playing));
    $('prev').addEventListener('click', () => step(-1));
    $('next').addEventListener('click', () => step(1));
    root.querySelector('.m-explore__frame').addEventListener('pointerenter', (e) => { if (e.pointerType === 'mouse') { hovering = true; sync(); } });
    root.querySelector('.m-explore__frame').addEventListener('pointerleave', () => { hovering = false; sync(); });
    document.addEventListener('visibilitychange', sync);
    new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; sync(); }, { threshold: 0.35 }).observe(stage);

    stage.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') { e.preventDefault(); step(-1); }
        if (e.key === 'ArrowRight') { e.preventDefault(); step(1); }
        if (e.target !== stage) return;
        if (e.key === ' ') { e.preventDefault(); setPlaying(!playing); }
        if (e.key === 'Enter') showLightbox(true);
    });

    // Geser (swipe) di layar sentuh; geseran tidak ikut membuka layar penuh.
    let touchX = null;
    let swiped = false;
    stage.addEventListener('pointerdown', (e) => {
        swiped = false;
        if (e.pointerType !== 'mouse') touchX = e.clientX;
    });
    stage.addEventListener('pointerup', (e) => {
        if (touchX === null) return;
        const dx = e.clientX - touchX;
        touchX = null;
        if (Math.abs(dx) > 40) {
            swiped = true;
            step(dx < 0 ? 1 : -1);
        }
    });

    // ---------------- Lightbox ----------------
    const lbImg = lightbox.querySelector('img');
    const showLightbox = (open = false) => {
        const slide = slides[list[pos]];
        lbImg.src = slide.url;
        lbImg.alt = slide.caption;
        lightbox.querySelector('[data-lb-caption]').textContent = `${slide.caption} · ${metaText(slide)}`;
        lightbox.querySelector('[data-lb-counter]').textContent = `${pos + 1} / ${list.length}`;
        lightbox.classList.toggle('is-single', list.length < 2);
        if (open && !lightbox.open) {
            lightbox.showModal();
            sync();
        }
    };
    $('zoom').addEventListener('click', () => showLightbox(true));
    stage.querySelectorAll('.m-explore__img').forEach((img) => img.addEventListener('click', () => { if (!swiped) showLightbox(true); }));
    lightbox.querySelector('[data-lb="prev"]').addEventListener('click', () => step(-1));
    lightbox.querySelector('[data-lb="next"]').addEventListener('click', () => step(1));
    lightbox.querySelector('[data-lb="close"]').addEventListener('click', () => lightbox.close());
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) lightbox.close(); });
    lightbox.addEventListener('close', sync);
    lightbox.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') step(-1);
        if (e.key === 'ArrowRight') step(1);
    });

    // ---------------- Filter ----------------
    const matches = (slide) => {
        const placeOk = filter.place === ''
            || (filter.place === 'none' ? slide.place === null : slide.place === Number(filter.place));
        const memberOk = filter.member === '' || slide.member === Number(filter.member);
        return placeOk && memberOk;
    };

    function setFilter(change) {
        Object.assign(filter, change);
        let next = slides.map((_, i) => i).filter((i) => matches(slides[i]));

        // Kombinasi filter kosong: lepas filter anggota agar lokasi yang dipilih tetap tampil.
        if (!next.length && 'place' in change && filter.member !== '') {
            filter.member = '';
            next = slides.map((_, i) => i).filter((i) => matches(slides[i]));
        }
        if (!next.length) return;

        if (placeSelect) placeSelect.value = filter.place;
        if (memberSelect) memberSelect.value = filter.member;

        list = next;
        trail = [...new Set(list.map((i) => slides[i].place).filter((p) => p !== null))];
        trailLine?.setLatLngs(trail.map((i) => [places[i].lat, places[i].lng]));
        root.classList.toggle('is-filtered', filter.place !== '' || filter.member !== '');

        pos = 0;
        lastPlace = undefined;
        buildStrip();
        fitTrail();
        lastPlace = slides[list[0]].place; // sudah di-fit, tidak perlu terbang lagi
        render();
    }

    placeSelect?.addEventListener('change', () => setFilter({ place: placeSelect.value }));
    memberSelect?.addEventListener('change', () => setFilter({ member: memberSelect.value }));

    setFilter({});
})();
