@vite('resources/css/sidebar.css')

<style>
/* Nonaktifkan semua tombol buka/tutup (toggle & collapse) sidebar di semua halaman */
.meui-sidebar-toggle,
.meui-sidebar-mobile-close,
.fi-topbar-collapse-sidebar-btn-ctn,
.fi-topbar-open-sidebar-btn,
.fi-topbar-close-sidebar-btn,
.fi-sidebar-open-collapse-sidebar-btn,
.fi-sidebar-close-collapse-sidebar-btn,
.fi-layout-sidebar-toggle-btn-ctn,
.fi-layout-sidebar-toggle-btn,
[data-sidebar-open],
[data-sidebar-close] {
    display: none !important;
}

/* Pastikan rail dan sidebar selalu terbuka penuh di desktop */
@media (min-width: 1024px) {
    .fi-body:has(> .meui-root) {
        --meui-rail-width: var(--sidebar-width, 17.5rem) !important;
    }

    .fi-body:has(> .meui-root) .meui-header-shell {
        margin-inline-start: var(--sidebar-width, 17.5rem) !important;
    }

    .fi-body:has(> .meui-root) .meui-sidebar-component {
        width: var(--sidebar-width, 17.5rem) !important;
    }

    .fi-body:has(> .meui-root) .meui-sidebar {
        width: var(--sidebar-width, 17.5rem) !important;
        transform: none !important;
    }

    .fi-body:has(> .meui-root) .meui-sidebar-caption,
    .fi-body:has(> .meui-root) .meui-sidebar-search,
    .fi-body:has(> .meui-root) .meui-sidebar .meui-brand-name,
    .fi-body:has(> .meui-root) .meui-sidebar .meui-profile-card-text,
    .fi-body:has(> .meui-root) .meui-sidebar .meui-profile-chevron {
        display: block !important;
    }
}
</style>
