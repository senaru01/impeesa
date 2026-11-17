<nav class="m-subnav no-print" aria-label="Menu toko">
    <a href="{{ route('admin.store.pos') }}" @class(['is-active' => request()->routeIs('admin.store.pos')])>Kasir</a>
    <a href="{{ route('admin.store.items.index') }}" @class(['is-active' => request()->routeIs('admin.store.items.*')])>Item &amp; stok</a>
    <a href="{{ route('admin.store.filament.index') }}" @class(['is-active' => request()->routeIs('admin.store.filament.*')])>Filament</a>
    <a href="{{ route('admin.store.sales.index') }}" @class(['is-active' => request()->routeIs('admin.store.sales.*')])>Penjualan &amp; arus kas</a>
    <button type="button" class="m-subnav__install" data-pwa-install hidden>
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>
        Pasang aplikasi kasir
    </button>
</nav>
