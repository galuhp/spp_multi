<div class="mt-4 border-t border-slate-200 pt-4">
    <p class="mb-2 text-center text-[11px] font-semibold uppercase tracking-wider text-slate-500">Akses Akun Demo Cepat</p>
    <div class="grid grid-cols-3 gap-2">
        <button type="button" onclick="fillDemo('admin', 'password')"
            class="rounded-lg border border-purple-200 bg-purple-50 px-2 py-2 text-xs font-semibold text-purple-700 transition hover:bg-purple-100">
            Admin
        </button>
        <button type="button" onclick="fillDemo('petugas', 'password')"
            class="rounded-lg border border-blue-200 bg-blue-50 px-2 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
            Petugas
        </button>
        <button type="button" onclick="fillDemo('siswa', 'password')"
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
            Siswa
        </button>
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