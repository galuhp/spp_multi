@php($profilAplikasi = \App\Models\ProfilSekolah::current())
<!DOCTYPE html>
<html lang="id" class="h-full bg-zinc-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem Pembayaran SPP') - {{ $profilAplikasi->appName() }}</title>
    @include('partials.app-icon')
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
    @wirekitStyles
</head>
<body class="h-full text-zinc-800 antialiased flex flex-col min-h-screen">
    <!-- Mobile Navigation Drawer -->
    <div id="mobile-sidebar-drawer" class="no-print hidden fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true">
        <div data-sidebar-close class="fixed inset-0 bg-zinc-950/60 backdrop-blur-xs"></div>
        <aside class="relative z-10 w-72 max-w-[85vw] h-full flex flex-col bg-white border-r border-zinc-200 shadow-xl">
            @include('layouts.partials.sidebar-content', ['mobile' => true])
        </aside>
    </div>

    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Navigation (Desktop) -->
        <aside class="no-print hidden md:flex md:w-64 md:flex-col bg-white border-r border-zinc-200 shrink-0">
            @include('layouts.partials.sidebar-content', ['mobile' => false])
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 bg-zinc-50">
            <!-- Header Bar -->
            <header class="no-print relative z-30 bg-white border-b border-zinc-200 px-4 sm:px-6 py-3.5 flex items-center justify-between gap-3 shadow-xs">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" data-sidebar-open class="hidden p-2 rounded-lg border border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-100 transition cursor-pointer" aria-label="Buka menu navigasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 class="text-base sm:text-lg font-bold text-zinc-900 truncate">
                        @yield('header_title', 'Dashboard')
                    </h2>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-zinc-100 border border-zinc-200 text-xs text-zinc-600 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                    </span>

                    <!-- User Profile Menu (Top Right) -->
                    @auth
                        @php $headerUser = auth()->user(); @endphp
                        <details data-dropdown class="relative z-40">
                            <summary class="list-none cursor-pointer outline-none flex items-center gap-2 p-1 sm:px-2.5 sm:py-1.5 rounded-xl border border-zinc-200 bg-white hover:bg-zinc-50 transition select-none shadow-xs">
                                <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
                                    {{ strtoupper(substr($headerUser->name, 0, 2)) }}
                                </div>
                                <div class="hidden sm:flex flex-col text-left leading-tight">
                                    <span class="text-xs font-bold text-zinc-800 max-w-[120px] truncate">
                                        {{ $headerUser->name }}
                                    </span>
                                    <span class="text-[10px] text-zinc-500">
                                        {{ $headerUser->role->label() }}
                                    </span>
                                </div>
                                <svg class="w-3.5 h-3.5 text-zinc-400 hidden sm:block shrink-0 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>

                            <!-- Dropdown Box -->
                            <div class="absolute end-0 mt-2 w-64 rounded-xl border border-zinc-200 bg-white p-2 text-zinc-800 shadow-xl z-50">
                                <!-- User Identity Header -->
                                <div class="p-3 rounded-lg bg-zinc-50 border border-zinc-200 mb-1.5">
                                    <div class="font-bold text-xs text-zinc-900 truncate">
                                        {{ $headerUser->name }}
                                    </div>
                                    <div class="text-[11px] font-mono text-zinc-500 truncate mt-0.5">
                                        {{ '@' . $headerUser->username }} {{ $headerUser->email ? '&bull; ' . $headerUser->email : '' }}
                                    </div>
                                    <div class="mt-2">
                                        <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded-full {{ $headerUser->role->badgeClass() }}">
                                            {{ $headerUser->role->label() }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Menu Items -->
                                <div class="space-y-0.5 text-xs font-medium">
                                    <a href="{{ route('profil.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition {{ request()->routeIs('profil.*') ? 'bg-zinc-100 text-zinc-900 font-bold' : '' }}">
                                        <svg class="w-4 h-4 opacity-75 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Profil Pengguna Saya</span>
                                    </a>

                                    @if($headerUser->isAdmin())
                                        <a href="{{ route('sekolah.edit') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition {{ request()->routeIs('sekolah.*') ? 'bg-zinc-100 text-zinc-900 font-bold' : '' }}">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <svg class="w-4 h-4 opacity-75 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                <span>Profil Sekolah</span>
                                            </div>
                                            <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md bg-indigo-100 text-zinc-900">Admin</span>
                                        </a>

                                        <a href="{{ route('user.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition {{ request()->routeIs('user.*') ? 'bg-zinc-100 text-zinc-900 font-bold' : '' }}">
                                            <svg class="w-4 h-4 opacity-75 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            <span>Kelola Hak Akses</span>
                                        </a>

                                        <a href="{{ route('log.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition {{ request()->routeIs('log.*') ? 'bg-zinc-100 text-zinc-900 font-bold' : '' }}">
                                            <svg class="w-4 h-4 opacity-75 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span>Log Aktivitas</span>
                                        </a>
                                    @endif

                                    @if($headerUser->isSiswa())
                                        <a href="{{ route('portal.tagihan') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition {{ request()->routeIs('portal.tagihan') ? 'bg-zinc-100 text-zinc-900 font-bold' : '' }}">
                                            <svg class="w-4 h-4 opacity-75 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                            <span>Tagihan SPP Saya</span>
                                        </a>

                                        <a href="{{ route('portal.riwayat') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 transition {{ request()->routeIs('portal.riwayat') ? 'bg-zinc-100 text-zinc-900 font-bold' : '' }}">
                                            <svg class="w-4 h-4 opacity-75 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>Riwayat Pembayaran</span>
                                        </a>
                                    @endif
                                </div>

                                <div class="my-1.5 border-t border-zinc-200"></div>

                                <!-- Logout Action -->
                                <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer text-left text-xs">
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                        <span>Keluar (Logout)</span>
                                    </button>
                                </form>
                            </div>
                        </details>
                    @endauth
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <!-- Flash Notification Messages -->
                @if(session('success'))
                    <div class="no-print mb-6">
                        <x-ui.alerts variant="success" icon="check-circle">{{ session('success') }}</x-ui.alerts>
                    </div>
                @endif

                @if(session('error'))
                    <div class="no-print mb-6">
                        <x-ui.alerts variant="error" icon="x-circle">{{ session('error') }}</x-ui.alerts>
                    </div>
                @endif

                @if($errors->any())
                    <div class="no-print mb-6">
                        <x-ui.alerts variant="warning" icon="exclamation-triangle">
                            <div class="text-sm font-semibold mb-1">Periksa kembali data masukan Anda:</div>
                            <ul class="list-disc list-inside text-xs space-y-0.5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alerts>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    @wirekitScripts
</body>
</html>
