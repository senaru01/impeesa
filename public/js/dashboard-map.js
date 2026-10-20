(() => {
    const container = document.getElementById('climbMap');
    if (!container || !window.L) return;

    const points = window.CLIMB_POINTS || [];
    const map = L.map(container, { zoomControl: false, scrollWheelZoom: false, attributionControl: true })
        .setView([-2.5, 117.5], 4);
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
    baseLayers.Terang.addTo(map);
    L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);

    // Scroll-zoom only after the user interacts with the map, so the page still scrolls.
    map.once('click focus', () => map.scrollWheelZoom.enable());

    const escape = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const icon = (summit) => L.divIcon({
        className: `m-pin ${summit ? 'm-pin--summit' : 'm-pin--miss'}`,
        html: '<span></span>',
        iconSize: [20, 20],
        iconAnchor: [10, 10],
        popupAnchor: [0, -12],
    });

    const popupHtml = (p, index) => `
        <div class="m-popup">
            ${p.photos.length ? `
                <button type="button" class="m-popup__cover" data-point="${index}" data-photo="0" aria-label="Lihat foto ${escape(p.name)}">
                    <img src="${escape(p.photos[0].url)}" alt="">
                    ${p.photos.length > 1 ? `<span>${p.photos.length} foto</span>` : ''}
                </button>` : ''}
            <strong>${escape(p.name)}</strong>
            <span class="m-popup__count">${p.climbs.length}× didaki</span>
            <ul>
                ${p.climbs.map((c) => `<li>${escape(c.date)}${c.route ? ` · via ${escape(c.route)}` : ''}<em>${escape(c.status)}</em></li>`).join('')}
            </ul>
            ${p.photos.length > 1 ? `
                <div class="m-popup__thumbs">
                    ${p.photos.slice(1, 5).map((photo, i) => `
                        <button type="button" data-point="${index}" data-photo="${i + 1}" aria-label="Foto ${i + 2}">
                            <img src="${escape(photo.url)}" alt="" loading="lazy">
                        </button>`).join('')}
                </div>` : ''}
        </div>`;

    // ---------------- Lightbox ----------------
    const lightbox = document.getElementById('climbLightbox');
    const lbImg = lightbox?.querySelector('img');
    const lbCaption = lightbox?.querySelector('[data-lb-caption]');
    const lbCounter = lightbox?.querySelector('[data-lb-counter]');
    let current = { point: 0, photo: 0 };

    const showPhoto = () => {
        const photos = points[current.point].photos;
        const photo = photos[current.photo];
        lbImg.src = photo.url;
        lbImg.alt = photo.caption;
        lbCaption.textContent = `${photo.caption} · ${photo.date}`;
        lbCounter.textContent = `${current.photo + 1} / ${photos.length}`;
        lightbox.classList.toggle('is-single', photos.length < 2);
    };
    const step = (delta) => {
        const total = points[current.point].photos.length;
        current.photo = (current.photo + delta + total) % total;
        showPhoto();
    };

    if (lightbox) {
        // Capture phase: Leaflet stops click propagation inside popups.
        container.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-point][data-photo]');
            if (!trigger) return;
            current = { point: Number(trigger.dataset.point), photo: Number(trigger.dataset.photo) };
            showPhoto();
            lightbox.showModal();
        }, true);
        lightbox.querySelector('[data-lb="prev"]').addEventListener('click', () => step(-1));
        lightbox.querySelector('[data-lb="next"]').addEventListener('click', () => step(1));
        lightbox.querySelector('[data-lb="close"]').addEventListener('click', () => lightbox.close());
        lightbox.addEventListener('click', (e) => { if (e.target === lightbox) lightbox.close(); });
        lightbox.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') step(-1);
            if (e.key === 'ArrowRight') step(1);
        });
    }

    const markers = points.map((p, index) => L.marker([p.lat, p.lng], { icon: icon(p.summit), title: p.name, riseOnHover: true })
        .bindPopup(popupHtml(p, index), { maxWidth: 260, minWidth: 220 })
        .addTo(map));

    if (markers.length === 1) {
        map.setView(markers[0].getLatLng(), 8);
    } else if (markers.length > 1) {
        map.fitBounds(L.featureGroup(markers).getBounds(), { padding: [40, 40], maxZoom: 9 });
    }
})();
