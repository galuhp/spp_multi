<script>
    (function () {
        function resetSidebarOpen() {
            if (window.matchMedia && window.matchMedia('(min-width: 1024px)').matches) {
                if (window.Alpine && window.Alpine.store && window.Alpine.store('sidebar')) {
                    const sidebar = window.Alpine.store('sidebar');
                    if (sidebar && (!sidebar.isOpen || !sidebar.isOpenDesktop)) {
                        sidebar.isOpen = true;
                        sidebar.isOpenDesktop = true;
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', resetSidebarOpen);
        document.addEventListener('livewire:navigated', resetSidebarOpen);
        window.addEventListener('load', resetSidebarOpen);
        resetSidebarOpen();
    })();
</script>
