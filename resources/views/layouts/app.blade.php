<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Pembayaran SPP') - E-SPP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .print-only {
                display: block !important;
            }
        }
    </style>
</head>
<body class="h-full text-slate-800 antialiased flex flex-col min-h-screen">
    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Navigation (Desktop) -->
        <aside class="no-print hidden md:flex md:w-64 md:flex-col bg-slate-900 border-r border-slate-800 shrink-0">
            <div class="p-5 flex items-center gap-3 border-b border-slate-800 bg-slate-950/40">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 flex items-center justify-center text-white font-black text-xl shadow-md shadow-indigo-500/20">
                    S
                </div>
                <div>
                    <h1 class="font-bold text-white text-base tracking-wide">E-SPP SEKOLAH</h1>
                    <p class="text-xs text-slate-400">Sistem Manajemen Pembayaran</p>
                </div>
            </div>

            <!-- User Badge Box in Sidebar -->
            <div class="px-5 py-4 border-b border-slate-800/80 bg-slate-800/30">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-slate-700 text-indigo-300 font-bold flex items-center justify-center text-sm border border-slate-600">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="font-medium text-sm text-slate-200 truncate">{{ auth()->user()->name }}</div>
                        <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-semibold rounded-full {{ auth()->user()->role->badgeClass() }}">
                            {{ auth()->user()->role->label() }}
                        </span>
                    </div>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-1.5 text-sm">
                <!-- Dashboard (All) -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                @if(auth()->user()->isStaff())
                    <div class="pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-500 px-3">
                        Transaksi & Siswa
                    </div>

                    <!-- Transaksi SPP -->
                    <a href="{{ route('pembayaran.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('pembayaran.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Transaksi SPP
                    </a>

                    <!-- Data Siswa -->
                    <a href="{{ route('siswa.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('siswa.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Data Siswa
                    </a>

                    <!-- Laporan & Rekap -->
                    <a href="{{ route('laporan.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('laporan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Laporan Rekapitulasi
                    </a>
                @endif

                @if(auth()->user()->isAdmin())
                    <div class="pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-500 px-3">
                        Master Data & Pengaturan
                    </div>

                    <!-- Kelas -->
                    <a href="{{ route('kelas.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('kelas.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Data Kelas
                    </a>

                    <!-- Jurusan -->
                    <a href="{{ route('jurusan.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('jurusan.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                        Kompetensi Keahlian
                    </a>

                    <!-- Tarif SPP -->
                    <a href="{{ route('spp.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('spp.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        Tarif SPP
                    </a>

                    <!-- Manajemen User -->
                    <a href="{{ route('user.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('user.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Hak Akses Pengguna
                    </a>

                    <!-- Log Aktivitas -->
                    <a href="{{ route('log.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('log.*') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Log Aktivitas
                    </a>
                @endif

                @if(auth()->user()->isSiswa())
                    <div class="pt-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-slate-500 px-3">
                        Portal Siswa
                    </div>

                    <a href="{{ route('portal.tagihan') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('portal.tagihan') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        Tagihan SPP
                    </a>

                    <a href="{{ route('portal.riwayat') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg font-medium transition {{ request()->routeIs('portal.riwayat') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Riwayat Pembayaran
                    </a>
                @endif
            </nav>

            <!-- Logout Button in Sidebar Footer -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/40">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-rose-400 hover:bg-rose-950/40 hover:text-rose-300 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Keluar Sistem
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50">
            <!-- Header Bar -->
            <header class="no-print bg-white border-b border-slate-200 shadow-xs px-4 sm:px-6 py-3.5 flex items-center justify-between z-10">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold text-slate-800">
                        @yield('header_title', 'Dashboard')
                    </h2>
                </div>

                <div class="flex items-center gap-4">
                    <span class="hidden sm:inline-block text-xs text-slate-500 font-medium">
                        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                    </span>
                    <form action="{{ route('logout') }}" method="POST" class="md:hidden">
                        @csrf
                        <button type="submit" class="text-xs px-2.5 py-1.5 bg-rose-50 text-rose-600 rounded-md font-semibold border border-rose-200">
                            Keluar
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <!-- Flash Notification Messages -->
                @if(session('success'))
                    <div class="no-print mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-xs">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-sm font-medium">{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="no-print mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-xs">
                        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-sm font-medium">{{ session('error') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="no-print mb-6 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 shadow-xs">
                        <div class="text-sm font-semibold mb-1">Periksa kembali data masukan Anda:</div>
                        <ul class="list-disc list-inside text-xs space-y-0.5 text-amber-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
