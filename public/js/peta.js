(() => {
    const trails = window.TRAILS || [];
    const cards = [...document.querySelectorAll('.trail')];
    const groups = [...document.querySelectorAll('[data-region-group]')];
    const chips = [...document.querySelectorAll('#regionChips .chip')];
    const search = document.getElementById('trailSearch');
    const pdfOnly = document.getElementById('pdfOnly');
    const resultCount = document.getElementById('resultCount');
    const emptyState = document.getElementById('emptyState');

    const state = { region: '', query: '', pdf: false };

    // ---------------- Map ----------------
    const map = L.map('map', { zoomControl: false, scrollWheelZoom: false, attributionControl: true })
        .setView([-2.5, 117.5], 5);
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    const source = ' · Data jalur: <a href="https://astacala.org/bank-data">Astacala</a>';
    const esri = (service, label) => L.tileLayer(`https://server.arcgisonline.com/ArcGIS/rest/services/${service}/MapServer/tile/{z}/{y}/{x}`, {
        maxZoom: 17,
        attribution: `Tiles &copy; Esri — ${label}${source}`,
    });
    const baseLayers = {
        Gelap: esri('Canvas/World_Dark_Gray_Base', 'Esri, HERE, Garmin, &copy; OpenStreetMap contributors'),
        Satelit: esri('World_Imagery', 'Esri, Maxar, Earthstar Geographics'),
        Topografi: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
            attribution: 'Map &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA), &copy; OpenStreetMap contributors' + source,
        }),
    };
    baseLayers.Gelap.addTo(map);
    L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);

    // Enable scroll-zoom only after the user interacts with the map, so the page still scrolls.
    map.once('click focus', () => map.scrollWheelZoom.enable());

    const icon = L.divIcon({ className: 'pin', html: '<span></span>', iconSize: [18, 18], iconAnchor: [9, 9], popupAnchor: [0, -10] });

    const escape = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

    const popupHtml = (t) => `
        <div class="popup">
            ${t.photo ? `<img src="${t.photo}" alt="">` : ''}
            <strong>${escape(t.name)}</strong>
            <span>${escape(t.area)}</span>
            <div class="popup__files">
                ${['gpx', 'kmz', 'pdf'].map((f) => (t.files[f]
                    ? `<a href="${t.files[f]}" class="file file--${f}" target="_blank" rel="noopener" download>${f.toUpperCase()}</a>`
                    : `<span class="file file--off">${f.toUpperCase()}</span>`)).join('')}
            </div>
            <button type="button" class="popup__maps" data-gmaps="${t.id}">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2z"/><path d="M9 4v14M15 6v14"/></svg>
                View Maps
            </button>
        </div>`;

    const markers = new Map();
    trails.forEach((t) => {
        const marker = L.marker([t.lat, t.lng], { icon, title: t.name, riseOnHover: true })
            .bindPopup(popupHtml(t), { className: 'popup-wrap', maxWidth: 240 });
        marker.on('click', () => highlight(t.id));
        markers.set(t.id, marker);
    });
    const layer = L.featureGroup([...markers.values()]).addTo(map);

    const highlight = (id) => {
        cards.forEach((c) => {
            const on = Number(c.dataset.id) === id;
            c.classList.toggle('is-active', on);
            if (!on) return;
            // Open the card's region dropdown; on desktop also bring the card into view in the list.
            c.closest('details').open = true;
            if (window.matchMedia('(min-width: 961px)').matches) c.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    };

    const fit = () => {
        const visible = [...markers.values()].filter((m) => layer.hasLayer(m));
        if (visible.length) map.flyToBounds(L.featureGroup(visible).getBounds().pad(0.15), { duration: 0.8, maxZoom: 11 });
    };

    // ---------------- Filtering ----------------
    const apply = (refit = true) => {
        let total = 0;
        const visibleIds = new Set();

        cards.forEach((card) => {
            const show = (!state.region || card.dataset.region === state.region)
                && (!state.query || card.dataset.search.includes(state.query))
                && (!state.pdf || card.dataset.pdf === '1');
            card.hidden = !show;
            if (show) { total++; visibleIds.add(Number(card.dataset.id)); }
        });

        const filtering = state.region || state.query || state.pdf;
        groups.forEach((group) => {
            const count = group.querySelectorAll('.trail:not([hidden])').length;
            group.hidden = count === 0;
            group.querySelector('[data-region-count]').textContent = count;
            // While filtering, open every region that still has matches so results are visible.
            if (filtering && count > 0) group.open = true;
        });

        markers.forEach((marker, id) => {
            if (visibleIds.has(id)) layer.addLayer(marker); else layer.removeLayer(marker);
        });

        emptyState.hidden = total > 0;
        resultCount.textContent = `Menampilkan ${total} jalur`;
        if (refit) fit();
    };

    chips.forEach((chip) => chip.addEventListener('click', () => {
        state.region = chip.dataset.region;
        chips.forEach((c) => c.classList.toggle('is-active', c === chip));
        apply();
    }));

    let debounce;
    search.addEventListener('input', () => {
        clearTimeout(debounce);
        debounce = setTimeout(() => {
            state.query = search.value.trim().toLowerCase();
            apply();
        }, 200);
    });

    pdfOnly.addEventListener('change', () => {
        state.pdf = pdfOnly.checked;
        apply();
    });

    // "Lihat di peta" on each card
    document.querySelectorAll('[data-locate]').forEach((btn) => btn.addEventListener('click', () => {
        const id = Number(btn.dataset.locate);
        const marker = markers.get(id);
        highlight(id);
        if (window.matchMedia('(max-width: 960px)').matches) {
            document.getElementById('map').scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        map.flyTo(marker.getLatLng(), 11, { duration: 1 });
        map.once('moveend', () => marker.openPopup());
    }));

    // ---------------- GPX tracks ----------------
    const byId = new Map(trails.map((t) => [t.id, t]));
    const gpxCache = new Map();

    // Parses a GPX file into track lines ([[lat, lng], ...][]) and named waypoints.
    const loadGpx = (t) => {
        if (!gpxCache.has(t.id)) {
            gpxCache.set(t.id, fetch(t.files.gpx)
                .then((res) => {
                    if (!res.ok) throw new Error(`HTTP ${res.status}`);
                    return res.text();
                })
                .then((text) => {
                    const xml = new DOMParser().parseFromString(text, 'application/xml');
                    const toLatLng = (el) => [parseFloat(el.getAttribute('lat')), parseFloat(el.getAttribute('lon'))];
                    const valid = (ll) => Number.isFinite(ll[0]) && Number.isFinite(ll[1]) && !(ll[0] === 0 && ll[1] === 0);

                    const lines = [...xml.querySelectorAll('trkseg, rte')]
                        .map((seg) => [...seg.querySelectorAll('trkpt, rtept')].map(toLatLng).filter(valid))
                        .filter((line) => line.length > 1);

                    const waypoints = [...xml.querySelectorAll('wpt')]
                        .map((w) => ({ latlng: toLatLng(w), name: (w.querySelector('name')?.textContent || '').trim() }))
                        .filter((w) => valid(w.latlng));

                    return { lines, waypoints };
                })
                .catch((err) => {
                    gpxCache.delete(t.id); // allow a retry next time
                    throw err;
                }));
        }
        return gpxCache.get(t.id);
    };

    const trackLayer = (gpx) => {
        const group = L.featureGroup();
        gpx.lines.forEach((line) => {
            L.polyline(line, { color: '#0b2a4a', weight: 7, opacity: 0.55 }).addTo(group);
            L.polyline(line, { color: '#2f9bff', weight: 4, opacity: 1 }).addTo(group);
        });
        if (gpx.lines.length) {
            const first = gpx.lines[0][0];
            const last = gpx.lines[gpx.lines.length - 1].at(-1);
            L.circleMarker(first, { radius: 6, color: '#fff', weight: 2, fillColor: '#22c55e', fillOpacity: 1 }).bindTooltip('Awal lintasan').addTo(group);
            L.circleMarker(last, { radius: 6, color: '#fff', weight: 2, fillColor: '#e2231a', fillOpacity: 1 }).bindTooltip('Akhir lintasan').addTo(group);
        }
        // Label every waypoint like the reference map, unless there are too many to stay readable.
        const permanent = gpx.waypoints.length <= 25;
        gpx.waypoints.forEach((w) => {
            const marker = L.marker(w.latlng, { icon: wptIcon, title: w.name }).addTo(group);
            if (w.name) marker.bindTooltip(escape(w.name), { permanent, direction: 'top', offset: [0, -22], className: 'wpt-label' });
        });
        return group;
    };

    const wptIcon = L.divIcon({
        className: 'wpt',
        html: '<svg viewBox="0 0 24 32" width="22" height="30"><path d="M12 0C5.4 0 0 5.3 0 11.9 0 20.8 12 32 12 32s12-11.2 12-20.1C24 5.3 18.6 0 12 0z"/><circle cx="12" cy="12" r="4.5"/></svg>',
        iconSize: [22, 30],
        iconAnchor: [11, 30],
    });

    // Show the selected trail's track on the main map too.
    let mainTrack = null;
    const showMainTrack = (t) => {
        loadGpx(t).then((gpx) => {
            if (mainTrack) map.removeLayer(mainTrack);
            mainTrack = L.featureGroup(gpx.lines.map((line) => L.polyline(line, { color: '#2f9bff', weight: 3, opacity: 0.95 }))).addTo(map);
        }).catch(() => {});
    };
    markers.forEach((marker, id) => marker.on('popupopen', () => showMainTrack(byId.get(id))));

    // ---------------- View Maps modal (GPX on satellite + Google Maps) ----------------
    const modal = document.getElementById('gmapsModal');
    const frame = document.getElementById('gmapsFrame');
    const status = document.getElementById('trackStatus');
    const tabs = [...modal.querySelectorAll('[data-tab]')];
    const panes = [...modal.querySelectorAll('[data-pane]')];
    let trackMap = null;
    let modalTrack = null;
    let current = null;

    const ensureTrackMap = () => {
        if (trackMap) return trackMap;
        trackMap = L.map('trackMap', { zoomControl: false });
        L.control.zoom({ position: 'bottomright' }).addTo(trackMap);
        const imagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 18,
            attribution: 'Tiles &copy; Esri — Esri, Maxar, Earthstar Geographics · Data jalur: <a href="https://astacala.org/bank-data">Astacala</a>',
        });
        const labels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 18 });
        const topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
            attribution: 'Map &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA), &copy; OpenStreetMap contributors',
        });
        const satellite = L.layerGroup([imagery, labels]).addTo(trackMap);
        L.control.layers({ Satelit: satellite, Topografi: topo }, null, { position: 'topright' }).addTo(trackMap);
        return trackMap;
    };

    const setStatus = (text) => {
        status.hidden = !text;
        status.textContent = text || '';
    };

    const showTab = (name) => {
        tabs.forEach((tab) => {
            const on = tab.dataset.tab === name;
            tab.classList.toggle('is-active', on);
            tab.setAttribute('aria-selected', String(on));
        });
        panes.forEach((pane) => pane.classList.toggle('is-active', pane.dataset.pane === name));
        if (name === 'google' && current && frame.dataset.for !== String(current.id)) {
            frame.src = `https://maps.google.com/maps?q=${current.lat},${current.lng}&t=k&z=14&hl=id&output=embed`;
            frame.dataset.for = current.id;
        }
        if (name === 'track' && trackMap) setTimeout(() => trackMap.invalidateSize(), 50);
    };

    const openGmaps = (t) => {
        current = t;
        const ll = `${t.lat},${t.lng}`;
        const external = `https://www.google.com/maps/@?api=1&map_action=map&center=${ll}&zoom=14&basemap=satellite`;
        document.getElementById('gmapsTitle').textContent = t.name;
        document.getElementById('gmapsArea').textContent = t.area;
        document.getElementById('gmapsExternal').href = external;
        document.getElementById('trackDownload').href = t.files.gpx;

        // Browsers without <dialog> support fall back to opening Google Maps in a new tab.
        if (typeof modal.showModal !== 'function') {
            window.open(external, '_blank', 'noopener');
            return;
        }

        modal.showModal();
        showTab('track');
        const tm = ensureTrackMap();
        tm.invalidateSize();
        tm.setView([t.lat, t.lng], 13);
        if (modalTrack) tm.removeLayer(modalTrack);
        setStatus('Memuat lintasan GPX...');

        loadGpx(t).then((gpx) => {
            if (current !== t) return;
            modalTrack = trackLayer(gpx).addTo(tm);
            if (modalTrack.getLayers().length) {
                tm.fitBounds(modalTrack.getBounds().pad(0.1));
                setStatus('');
            } else {
                setStatus('File GPX ini tidak berisi lintasan.');
            }
            showMainTrack(t);
        }).catch(() => {
            if (current === t) setStatus('Lintasan GPX gagal dimuat. Coba lagi atau unduh file GPX-nya.');
        });
    };

    tabs.forEach((tab) => tab.addEventListener('click', () => showTab(tab.dataset.tab)));
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-gmaps]');
        if (btn) openGmaps(byId.get(Number(btn.dataset.gmaps)));
    });
    modal.querySelectorAll('[data-close]').forEach((el) => el.addEventListener('click', () => modal.close()));
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.close(); });
    modal.addEventListener('close', () => {
        frame.src = 'about:blank';
        delete frame.dataset.for;
        current = null;
    });

    // Leaflet needs a size refresh once the layout settles (fonts, view transition).
    window.addEventListener('load', () => map.invalidateSize());

    if (location.hash === '#cari') setTimeout(() => search.focus({ preventScroll: true }), 600);

    apply(false);
    map.fitBounds(layer.getBounds().pad(0.05));
})();
