<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem - E-SPP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-slate-800">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-tr from-indigo-600 to-blue-500 flex items-center justify-center text-white font-black text-3xl shadow-xl shadow-indigo-500/25 mb-3">
                S
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Sistem Pembayaran SPP</h1>
            <p class="text-slate-400 text-sm mt-1">Silakan masuk menggunakan akun Anda</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200/80 p-6 sm:p-8">
            @if(session('success'))
                <div class="mb-5 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="login" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Username / NISN / Email
                    </label>
                    <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus
                        placeholder="Contoh: admin, petugas, atau NISN"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition">
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Kata Sandi
                    </label>
                    <input type="password" id="password" name="password" required
                        placeholder="Masukkan kata sandi"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm transition">
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                        <span class="text-xs text-slate-600 font-medium">Ingat Saya</span>
                    </label>
                </div>

                <button type="submit" class="w-full mt-2 py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-sm transition shadow-md shadow-indigo-600/25">
                    Masuk ke Sistem
                </button>
            </form>

            <!-- Quick Demo Accounts Helper -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider text-center mb-3">
                    Akses Akun Demo Cepat
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="fillLogin('admin', 'password')"
                        class="px-2 py-2 rounded-lg border border-purple-200 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-semibold transition text-center">
                        Admin
                    </button>
                    <button type="button" onclick="fillLogin('petugas', 'password')"
                        class="px-2 py-2 rounded-lg border border-blue-200 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-semibold transition text-center">
                        Petugas
                    </button>
                    <button type="button" onclick="fillLogin('siswa', 'password')"
                        class="px-2 py-2 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold transition text-center">
                        Siswa
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
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
