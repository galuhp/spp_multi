<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 dark:bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <script>
        try {
            document.querySelector('meta[name="color-scheme"]').content = localStorage.getItem('color-scheme') ?? 'light dark';
        } catch {}
    </script>
    <title>@yield('title', 'Sistem Pembayaran SPP') - E-SPP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print,
            .prism-canvas::before {
                display: none !important;
            }
            html,
            body {
                background: white !important;
                color: black !important;
                color-scheme: light !important;
            }
            .print-only {
                display: block !important;
            }
        }
    </style>
</head>
<body class="prism-canvas h-full text-slate-800 dark:text-slate-100 antialiased flex flex-col min-h-screen">
    <!-- Top Prismatic Spectrum Hairline -->
    <div class="no-print h-0.5 w-full prism-spectrum-bar shrink-0"></div>

    <!-- Mobile Navigation Drawer -->
    <div id="prism-mobile-drawer" class="no-print hidden fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true">
        <div data-sidebar-close class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs"></div>
        <aside class="relative z-10 w-72 max-w-[85vw] h-full flex flex-col bg-white/95 dark:bg-slate-950/95 backdrop-blur-2xl border-r border-slate-200/80 dark:border-white/10 shadow-prism-lg">
            @include('layouts.partials.sidebar-content', ['mobile' => true])
        </aside>
    </div>

    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Navigation (Desktop) -->
        <aside class="no-print hidden md:flex md:w-64 md:flex-col bg-white/75 dark:bg-slate-950/75 backdrop-blur-2xl border-r border-slate-200/80 dark:border-white/10 shrink-0">
            @include('layouts.partials.sidebar-content', ['mobile' => false])
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header Bar -->
            <header class="no-print bg-white/70 dark:bg-slate-900/70 backdrop-blur-xl border-b border-slate-200/80 dark:border-white/10 px-4 sm:px-6 py-3.5 flex items-center justify-between gap-3 z-10">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" data-sidebar-open class="md:hidden p-2 rounded-xl border border-slate-200/80 dark:border-white/12 bg-white/80 dark:bg-slate-800/80 text-slate-600 dark:text-slate-200 hover:bg-prism-50 dark:hover:bg-slate-700/80 transition cursor-pointer" aria-label="Buka menu navigasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white truncate">
                        @yield('header_title', 'Dashboard')
                    </h2>
                </div>

                <div class="flex items-center gap-2.5 sm:gap-3.5 shrink-0">
                    <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100/80 dark:bg-white/6 border border-slate-200/70 dark:border-white/8 text-xs text-slate-600 dark:text-slate-300 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-prism-500 dark:bg-prism-400"></span>
                        {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                    </span>

                    <x-theme-toggle />

                    <form action="{{ route('logout') }}" method="POST" class="md:hidden">
                        @csrf
                        <button type="submit" class="text-xs px-2.5 py-2 bg-rose-50 dark:bg-rose-500/15 text-rose-600 dark:text-rose-300 rounded-xl font-semibold border border-rose-200/80 dark:border-rose-400/25 cursor-pointer">
                            Keluar
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <!-- Flash Notification Messages -->
                @if(session('success'))
                    <div class="no-print mb-6 p-4 rounded-2xl bg-emerald-50/90 dark:bg-emerald-500/12 border border-emerald-200/80 dark:border-emerald-400/25 text-emerald-800 dark:text-emerald-200 backdrop-blur-md flex items-start gap-3 shadow-xs">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-sm font-medium">{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="no-print mb-6 p-4 rounded-2xl bg-rose-50/90 dark:bg-rose-500/12 border border-rose-200/80 dark:border-rose-400/25 text-rose-800 dark:text-rose-200 backdrop-blur-md flex items-start gap-3 shadow-xs">
                        <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="text-sm font-medium">{{ session('error') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="no-print mb-6 p-4 rounded-2xl bg-amber-50/90 dark:bg-amber-500/12 border border-amber-200/80 dark:border-amber-400/25 text-amber-800 dark:text-amber-200 backdrop-blur-md shadow-xs">
                        <div class="text-sm font-semibold mb-1">Periksa kembali data masukan Anda:</div>
                        <ul class="list-disc list-inside text-xs space-y-0.5 text-amber-700 dark:text-amber-300">
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
