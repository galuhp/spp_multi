<div class="p-5 flex items-center justify-between gap-3 border-b border-zinc-200 bg-white">
    <div class="flex items-center gap-3 min-w-0">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-black text-xl shadow-md shrink-0">
            S
        </div>
        <div class="min-w-0">
            <h1 class="font-bold text-zinc-900 text-base tracking-wide truncate">E-SPP SEKOLAH</h1>
            <p class="text-xs text-zinc-500 truncate">Sistem Manajemen Pembayaran</p>
        </div>
    </div>
    @if($mobile ?? false)
        <button type="button" data-sidebar-close class="p-1.5 rounded-lg text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 cursor-pointer" aria-label="Tutup navigasi">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    @endif
</div>

<!-- User Badge Box in Sidebar -->
<div class="px-5 py-4 border-b border-zinc-200 bg-zinc-50">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center text-sm border border-indigo-200 shrink-0">
            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
        </div>
        <div class="overflow-hidden">
            <div class="font-medium text-sm text-zinc-900 truncate">{{ auth()->user()->name }}</div>
            <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-semibold rounded-full {{ auth()->user()->role->badgeClass() }}">
                {{ auth()->user()->role->label() }}
            </span>
        </div>
    </div>
</div>

<nav class="flex-1 overflow-y-auto px-4 py-5 space-y-1.5 text-sm">
    <!-- Dashboard (All) -->
    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
        <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
    </a>

    @if(auth()->user()->isStaff())
        <div class="pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-zinc-500 px-3">
            Transaksi & Siswa
        </div>

        <a href="{{ route('pembayaran.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('pembayaran.*') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Transaksi SPP
        </a>

        <a href="{{ route('siswa.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('siswa.*') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            Data Siswa
        </a>

        <a href="{{ route('laporan.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('laporan.*') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Laporan Rekapitulasi
        </a>
    @endif

    @if(auth()->user()->isAdmin())
        <div class="pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-zinc-500 px-3">
            Master Data & Pengaturan
        </div>

        <a href="{{ route('kelas.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('kelas.*') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Data Kelas
        </a>

        <a href="{{ route('jurusan.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('jurusan.*') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
            Kompetensi Keahlian
        </a>

        <a href="{{ route('spp.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('spp.*') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Tarif & Kategori
        </a>

        <a href="{{ route('user.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('user.*') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Hak Akses Pengguna
        </a>

        <a href="{{ route('log.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('log.*') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Log Aktivitas
        </a>
    @endif

    @if(auth()->user()->isSiswa())
        <div class="pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-zinc-500 px-3">
            Portal Siswa
        </div>

        <a href="{{ route('portal.tagihan') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('portal.tagihan') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Tagihan SPP
        </a>

        <a href="{{ route('portal.riwayat') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('portal.riwayat') ? 'bg-indigo-50 text-indigo-700 shadow-sm' : 'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900' }}">
            <svg class="w-5 h-5 opacity-80 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Riwayat Pembayaran
        </a>
    @endif
</nav>

<!-- Logout Button in Sidebar Footer -->
<div class="p-4 border-t border-zinc-200 bg-zinc-50">
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Keluar Sistem
        </button>
    </form>
</div>
