/**
 * E-SPP Application Scripts
 * Mobile navigation drawer & accessible dropdown management
 */

document.addEventListener('click', (event) => {
    const openDrawerBtn = event.target.closest('[data-sidebar-open]');
    if (openDrawerBtn) {
        const drawer = document.getElementById('mobile-sidebar-drawer');
        drawer?.classList.remove('hidden');
        return;
    }

    const closeDrawerBtn = event.target.closest('[data-sidebar-close]');
    if (closeDrawerBtn) {
        const drawer = document.getElementById('mobile-sidebar-drawer');
        drawer?.classList.add('hidden');
        return;
    }

    // Auto-close open dropdowns on click outside
    document.querySelectorAll('details[data-dropdown][open]').forEach((dropdown) => {
        if (!dropdown.contains(event.target)) {
            dropdown.removeAttribute('open');
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.getElementById('mobile-sidebar-drawer')?.classList.add('hidden');
        document.querySelectorAll('details[data-dropdown][open]').forEach((d) => d.removeAttribute('open'));
    }
});
