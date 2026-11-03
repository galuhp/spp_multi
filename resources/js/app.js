/**
 * Prism Theme — Color Scheme & Mobile Navigation Controller
 *
 * Implements system-aware two-state toggling backed by <meta name="color-scheme">.
 * When the target scheme matches the current OS preference, the stored override
 * is cleared so the page returns to following the system ("light dark").
 */
const STORAGE_KEY = 'color-scheme';

function getColorSchemeMeta() {
    return document.querySelector('meta[name="color-scheme"]');
}

function getSystemScheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function getRenderedScheme() {
    const content = getColorSchemeMeta()?.content?.trim();
    if (content === 'dark' || content === 'light') {
        return content;
    }

    return getSystemScheme();
}

function applyMetaScheme(value) {
    const meta = getColorSchemeMeta();
    if (meta) {
        meta.content = value || 'light dark';
    }
}

function toggleColorScheme() {
    const current = getRenderedScheme();
    const target = current === 'dark' ? 'light' : 'dark';
    const system = getSystemScheme();

    if (target === system) {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch {
            // Storage unavailable; apply in-memory override for current session
        }
        applyMetaScheme('light dark');
    } else {
        try {
            localStorage.setItem(STORAGE_KEY, target);
        } catch {
            // Storage unavailable; still apply visual override
        }
        applyMetaScheme(target);
    }
}

document.addEventListener('click', (event) => {
    const toggleBtn = event.target.closest('[data-theme-toggle]');
    if (toggleBtn) {
        event.preventDefault();
        toggleColorScheme();
        return;
    }

    const openDrawerBtn = event.target.closest('[data-sidebar-open]');
    if (openDrawerBtn) {
        const drawer = document.getElementById('prism-mobile-drawer');
        drawer?.classList.remove('hidden');
        return;
    }

    const closeDrawerBtn = event.target.closest('[data-sidebar-close]');
    if (closeDrawerBtn) {
        const drawer = document.getElementById('prism-mobile-drawer');
        drawer?.classList.add('hidden');
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.getElementById('prism-mobile-drawer')?.classList.add('hidden');
    }
});

window.addEventListener('storage', (event) => {
    if (event.key === STORAGE_KEY) {
        applyMetaScheme(event.newValue ?? 'light dark');
    }
});
