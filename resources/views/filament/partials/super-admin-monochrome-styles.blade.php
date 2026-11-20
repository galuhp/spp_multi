<style>
/* Super Admin — Monochrome Black & White (shadcn/Zinc style) */
:root {
    --sa-bg: #ffffff;
    --sa-subtle: #fafafa;
    --sa-muted: #f4f4f5;
    --sa-border: #e4e4e7;
    --sa-fg: #09090b;
    --sa-fg-muted: #71717a;
}

.dark {
    --sa-bg: #09090b;
    --sa-subtle: #111113;
    --sa-muted: #27272a;
    --sa-border: #27272a;
    --sa-fg: #fafafa;
    --sa-fg-muted: #a1a1aa;
}

/* Primary Buttons: Black on Light, White on Dark */
.fi-btn.fi-color-primary:not(.fi-outlined) {
    background-color: var(--sa-fg) !important;
    color: var(--sa-bg) !important;
    border-color: var(--sa-fg) !important;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
}

.fi-btn.fi-color-primary:not(.fi-outlined):hover {
    opacity: 0.9;
}

.fi-btn.fi-color-primary.fi-outlined {
    color: var(--sa-fg) !important;
    border-color: var(--sa-border) !important;
}

/* Active Sidebar Item */
.fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
    background-color: var(--sa-muted) !important;
    color: var(--sa-fg) !important;
}

.fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-icon,
.fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-label {
    color: var(--sa-fg) !important;
}

/* Clean Borders on Sections, Tables & Stats */
.fi-section,
.fi-ta-ctn,
.fi-wi-stats-overview-stat {
    border-color: var(--sa-border) !important;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.03) !important;
}

/* Links & Numeric Alignment */
.fi-ta-text-item-label,
.fi-wi-stats-overview-stat-value {
    font-variant-numeric: tabular-nums;
}

.fi-link.fi-color-primary {
    color: var(--sa-fg) !important;
    text-decoration-color: var(--sa-fg-muted);
}
</style>
