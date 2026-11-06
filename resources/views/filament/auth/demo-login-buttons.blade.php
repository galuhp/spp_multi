<div class="mt-4">
    <div class="relative flex items-center justify-center text-center">
        <div class="absolute h-px w-full border-t border-gray-200"></div>
        <p class="relative inline-block rounded-full bg-white p-2 text-sm font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-100">
            Akses Akun Demo Cepat
        </p>
    </div>
    <div class="mt-4" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem;">
        <x-filament::button
            type="button"
            color="primary"
            size="sm"
            outlined
            onclick="fillDemo('admin', 'password')"
        >
            Admin
        </x-filament::button>
        <x-filament::button
            type="button"
            color="info"
            size="sm"
            outlined
            onclick="fillDemo('petugas', 'password')"
        >
            Petugas
        </x-filament::button>
        <x-filament::button
            type="button"
            color="success"
            size="sm"
            outlined
            onclick="fillDemo('siswa', 'password')"
        >
            Siswa
        </x-filament::button>
    </div>
</div>

<script>
    function fillDemo(username, password) {
        var u = document.getElementById('form.username');
        var p = document.getElementById('form.password');
        if (u) { u.value = username; u.dispatchEvent(new Event('input', { bubbles: true })); }
        if (p) { p.value = password; p.dispatchEvent(new Event('input', { bubbles: true })); }
    }
</script>