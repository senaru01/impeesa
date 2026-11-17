/*
 * Service worker aplikasi Kasir Impeesa (PWA khusus menu Toko, scope /admin/toko).
 * Disajikan lewat route /admin/toko/sw.js dengan header Service-Worker-Allowed.
 * - Halaman: selalu dari jaringan (stok, harga & token CSRF selalu baru). Offline → /offline.html.
 * - Aset statis (css, js, ikon, gambar publik, font): stale-while-revalidate supaya cepat.
 */
const VERSION = 'impeesa-toko-v1';
const STATIC_CACHE = `${VERSION}-static`;
const OFFLINE_URL = '/offline.html';
const PRECACHE = [OFFLINE_URL, '/icons/icon-192.png', '/icons/icon-512.png', '/toko.webmanifest'];
const STATIC_PATHS = ['/css/', '/js/', '/icons/', '/images/'];
const FONT_HOSTS = ['fonts.bunny.net'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('impeesa-toko-') && !key.startsWith(VERSION)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

const isStatic = (url) => (url.origin === self.location.origin && STATIC_PATHS.some((path) => url.pathname.startsWith(path)))
    || FONT_HOSTS.includes(url.hostname);

const staleWhileRevalidate = async (request) => {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response.ok || response.type === 'opaque') {
                cache.put(request, response.clone());
            }

            return response;
        })
        .catch(() => cached);

    return cached || network;
};

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));

        return;
    }

    if (isStatic(url)) {
        event.respondWith(staleWhileRevalidate(request));
    }
});
