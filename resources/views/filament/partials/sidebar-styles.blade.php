@vite('resources/css/sidebar.css')

<style>
/* Sembunyikan tombol toggle topbar default pada desktop agar hanya tombol garis vertikal yang aktif */
@media (min-width: 1024px) {
    .meui-sidebar-toggle,
    .fi-topbar-collapse-sidebar-btn-ctn,
    .fi-topbar-open-sidebar-btn,
    .fi-topbar-close-sidebar-btn,
    .fi-sidebar-open-collapse-sidebar-btn,
    .fi-sidebar-close-collapse-sidebar-btn,
    .fi-layout-sidebar-toggle-btn-ctn,
    .fi-layout-sidebar-toggle-btn {
        display: none !important;
    }

    /* Animasi transisi lebar sidebar dan margin konten yang mulus */
    .fi-body:has(> .meui-root) .meui-sidebar-component,
    .fi-body:has(> .meui-root) .meui-sidebar,
    .fi-body:has(> .meui-root) .meui-header-shell,
    .fi-body:has(> .meui-root) .fi-main-ctn {
        transition: width 200ms cubic-bezier(0.4, 0, 0.2, 1),
                    margin-inline-start 200ms cubic-bezier(0.4, 0, 0.2, 1);
    }
}

/* Tombol buka-tutup di samping sidebar (tepat di garis vertikal pembatas sidebar) */
.sidebar-divider-toggle-wrapper {
    position: absolute;
    top: 21px;
    right: -13px;
    z-index: 50;
    display: none;
}

@media (min-width: 1024px) {
    .sidebar-divider-toggle-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}

[dir="rtl"] .sidebar-divider-toggle-wrapper {
    right: auto;
    left: -13px;
}

.sidebar-divider-toggle-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    padding: 0;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
    background-color: #ffffff;
    color: #64748b;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08), 0 1px 2px -1px rgba(0, 0, 0, 0.08);
    cursor: pointer;
    transition: all 180ms cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
}

/* Memperluas area klik (hit area) untuk UX interaksi yang optimal */
.sidebar-divider-toggle-btn::before {
    content: '';
    position: absolute;
    inset: -6px;
}

.sidebar-divider-toggle-btn:hover {
    background-color: #f8fafc;
    color: var(--primary-600, #2563eb);
    border-color: #cbd5e1;
    transform: scale(1.1);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.06);
}

.sidebar-divider-toggle-btn:active {
    transform: scale(0.95);
}

.sidebar-divider-toggle-btn:focus-visible {
    box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px var(--primary-500, #3b82f6);
}

.dark .sidebar-divider-toggle-btn {
    border-color: #334155;
    background-color: #1e293b;
    color: #94a3b8;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4);
}

.dark .sidebar-divider-toggle-btn:hover {
    background-color: #334155;
    color: #ffffff;
    border-color: #475569;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
}

.dark .sidebar-divider-toggle-btn:focus-visible {
    box-shadow: 0 0 0 2px #0f172a, 0 0 0 4px var(--primary-400, #60a5fa);
}

[dir="rtl"] .sidebar-divider-toggle-btn svg {
    transform: scaleX(-1);
}
</style>
