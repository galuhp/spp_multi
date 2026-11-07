<div
    x-data="{}"
    class="sidebar-divider-toggle-wrapper"
>
    <button
        type="button"
        x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        x-bind:title="$store.sidebar.isOpen ? 'Tutup sidebar' : 'Buka sidebar'"
        x-bind:aria-label="$store.sidebar.isOpen ? 'Tutup navigasi sidebar' : 'Buka navigasi sidebar'"
        aria-controls="fi-main-sidebar"
        x-bind:aria-expanded="$store.sidebar.isOpen ? 'true' : 'false'"
        class="sidebar-divider-toggle-btn"
    >
        {{-- Icon saat sidebar terbuka: Chevron Left (arah menutup/collapse) --}}
        <svg
            x-show="$store.sidebar.isOpen"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-3.5 w-3.5"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" />
        </svg>

        {{-- Icon saat sidebar tertutup/collapsed: Chevron Right (arah membuka/expand) --}}
        <svg
            x-cloak
            x-show="! $store.sidebar.isOpen"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            class="h-3.5 w-3.5"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>
</div>
