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
    <title>Masuk Sistem - E-SPP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="prism-canvas min-h-full flex flex-col justify-center items-center p-4 sm:p-6 antialiased text-slate-800 dark:text-slate-100 relative">
    <!-- Top Spectrum Accent -->
    <div class="fixed top-0 inset-x-0 h-1 prism-spectrum-bar z-20"></div>

    <!-- Floating Theme Switcher -->
    <div class="fixed top-4 right-4 z-20">
        <x-theme-toggle :show-label="true" />
    </div>

    <div class="w-full max-w-md my-8">
        <!-- Logo & Title -->
        <div class="text-center mb-7">
            <x-prism-mark size="lg" class="mx-auto mb-3.5" />
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Sistem Pembayaran SPP</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Silakan masuk menggunakan akun Anda</p>
        </div>

        <!-- Login Card -->
        <div class="prism-card overflow-hidden">
            <div class="h-1 w-full prism-spectrum-bar"></div>

            <div class="p-6 sm:p-8">
                @if(session('success'))
                    <div class="mb-5 p-3 rounded-xl bg-emerald-50/90 dark:bg-emerald-500/15 border border-emerald-200/80 dark:border-emerald-400/25 text-emerald-800 dark:text-emerald-200 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 p-3 rounded-xl bg-rose-50/90 dark:bg-rose-500/15 border border-rose-200/80 dark:border-rose-400/25 text-rose-800 dark:text-rose-200 text-sm">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 p-3 rounded-xl bg-rose-50/90 dark:bg-rose-500/15 border border-rose-200/80 dark:border-rose-400/25 text-rose-800 dark:text-rose-200 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="login" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                            Username / NISN / Email
                        </label>
                        <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus
                            placeholder="Contoh: admin, petugas, atau NISN"
                            class="w-full px-3.5 py-2.5 prism-input text-sm">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-1.5">
                            Kata Sandi
                        </label>
                        <input type="password" id="password" name="password" required
                            placeholder="Masukkan kata sandi"
                            class="w-full px-3.5 py-2.5 prism-input text-sm">
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 dark:border-slate-600">
                            <span class="text-xs text-slate-600 dark:text-slate-300 font-medium">Ingat Saya</span>
                        </label>
                    </div>

                    <button type="submit" class="w-full mt-2 py-2.5 px-4 prism-btn-primary text-sm">
                        Masuk ke Sistem
                    </button>
                </form>

                <!-- Quick Demo Accounts Helper -->
                <div class="mt-6 pt-5 border-t border-slate-200/70 dark:border-white/10">
                    <div class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-center mb-3">
                        Akses Akun Demo Cepat
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="fillLogin('admin', 'password')"
                            class="px-2 py-2 rounded-xl border border-purple-200/80 dark:border-purple-400/25 bg-purple-50/80 dark:bg-purple-500/12 hover:bg-purple-100 dark:hover:bg-purple-500/22 text-purple-700 dark:text-purple-300 text-xs font-semibold transition text-center cursor-pointer">
                            Admin
                        </button>
                        <button type="button" onclick="fillLogin('petugas', 'password')"
                            class="px-2 py-2 rounded-xl border border-sky-200/80 dark:border-sky-400/25 bg-sky-50/80 dark:bg-sky-500/12 hover:bg-sky-100 dark:hover:bg-sky-500/22 text-sky-700 dark:text-sky-300 text-xs font-semibold transition text-center cursor-pointer">
                            Petugas
                        </button>
                        <button type="button" onclick="fillLogin('siswa', 'password')"
                            class="px-2 py-2 rounded-xl border border-emerald-200/80 dark:border-emerald-400/25 bg-emerald-50/80 dark:bg-emerald-500/12 hover:bg-emerald-100 dark:hover:bg-emerald-500/22 text-emerald-700 dark:text-emerald-300 text-xs font-semibold transition text-center cursor-pointer">
                            Siswa
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 dark:text-slate-400 mt-6">
            &copy; {{ date('Y') }} Sistem Informasi Manajemen SPP Sekolah
        </p>
    </div>

    <script>
        function fillLogin(username, password) {
            document.getElementById('login').value = username;
            document.getElementById('password').value = password;
        }
    </script>
</body>
</html>
