<nav class="m-subnav no-print" aria-label="Menu toko">
    <a href="{{ route('admin.store.pos') }}" @class(['is-active' => request()->routeIs('admin.store.pos')])>Kasir</a>
    <a href="{{ route('admin.store.items.index') }}" @class(['is-active' => request()->routeIs('admin.store.items.*')])>Item &amp; stok</a>
    <a href="{{ route('admin.store.filament.index') }}" @class(['is-active' => request()->routeIs('admin.store.filament.*')])>Filament</a>
    <a href="{{ route('admin.store.sales.index') }}" @class(['is-active' => request()->routeIs('admin.store.sales.*')])>Penjualan &amp; arus kas</a>
</nav>
