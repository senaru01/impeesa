/* Aplikasi Kasir Impeesa: daftarkan service worker toko & tombol "Pasang aplikasi kasir". */
(() => {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/admin/toko/sw.js', { scope: '/admin/toko' }).catch(() => {});
        });
    }

    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    // iPadOS 13+ mengaku sebagai Mac, jadi cek juga dukungan layar sentuh.
    const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

    let deferredPrompt = null;
    const toggle = (visible) => document.querySelectorAll('[data-pwa-install]').forEach((button) => {
        button.hidden = !visible;
    });

    // iOS tidak mengenal beforeinstallprompt: tampilkan tombol yang membuka panduan manual.
    if (isIos && !isStandalone) {
        document.addEventListener('DOMContentLoaded', () => toggle(true));
    }

    const showIosGuide = () => {
        let dialog = document.getElementById('pwa-ios-guide');

        if (!dialog) {
            dialog = document.createElement('dialog');
            dialog.id = 'pwa-ios-guide';
            dialog.className = 'm-pwa-guide';
            dialog.innerHTML = `
                <h3>Pasang aplikasi kasir</h3>
                <ol>
                    <li>Buka halaman ini di <strong>Safari</strong>.</li>
                    <li>Ketuk tombol <strong>Bagikan</strong>
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="ikon Bagikan"><path d="M12 3v12M8 7l4-4 4 4M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7"/></svg>
                        di bilah bawah/atas.</li>
                    <li>Pilih <strong>Tambahkan ke Layar Utama</strong> (Add to Home Screen), lalu ketuk <strong>Tambah</strong>.</li>
                </ol>
                <form method="dialog"><button type="submit">Mengerti</button></form>`;
            document.body.appendChild(dialog);
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    dialog.close();
                }
            });
        }

        dialog.showModal();
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt = event;
        toggle(true);
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        toggle(false);
    });

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-pwa-install]');

        if (!button) {
            return;
        }

        if (!deferredPrompt) {
            if (isIos) {
                event.preventDefault();
                showIosGuide();
            }

            return;
        }

        event.preventDefault();
        deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt = null;
        toggle(false);
    });
})();
