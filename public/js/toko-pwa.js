/* Aplikasi Kasir Impeesa: daftarkan service worker toko & tombol "Pasang aplikasi kasir". */
(() => {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/admin/toko/sw.js', { scope: '/admin/toko' }).catch(() => {});
        });
    }

    let deferredPrompt = null;
    const toggle = (visible) => document.querySelectorAll('[data-pwa-install]').forEach((button) => {
        button.hidden = !visible;
    });

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

        if (!button || !deferredPrompt) {
            return;
        }

        event.preventDefault();
        deferredPrompt.prompt();
        await deferredPrompt.userChoice;
        deferredPrompt = null;
        toggle(false);
    });
})();
