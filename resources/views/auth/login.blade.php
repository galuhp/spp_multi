<!DOCTYPE html>
<html lang="id" class="h-full bg-zinc-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem - E-SPP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center p-4 antialiased text-zinc-900">
    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-zinc-900 flex items-center justify-center text-white font-black text-3xl shadow-xl mb-3">
                S
            </div>
            <h1 class="text-2xl font-bold tracking-tight">Sistem Pembayaran SPP</h1>
            <p class="text-zinc-500 text-sm mt-1">Silakan masuk menggunakan akun Anda</p>
        </div>

        <!-- Login Card -->
        <x-ui.card class="!max-w-none">
            @if(session('success'))
                <div class="mb-5">
                    <x-ui.alerts variant="success" icon="check-circle">{{ session('success') }}</x-ui.alerts>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5">
                    <x-ui.alerts variant="error" icon="x-circle">{{ session('error') }}</x-ui.alerts>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5">
                    <x-ui.alerts variant="error" icon="exclamation-triangle">{{ $errors->first() }}</x-ui.alerts>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="login" class="mb-1.5 block text-sm font-medium text-zinc-700">
                        Username / NISN / Email
                    </label>
                    <x-ui.input id="login" name="login" value="{{ old('login') }}" required autofocus
                        placeholder="Contoh: admin, petugas, atau NISN" left-icon="user" />
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-700">
                        Kata Sandi
                    </label>
                    <x-ui.input id="password" name="password" type="password" required
                        placeholder="Masukkan kata sandi" left-icon="lock-closed" />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="size-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500">
                        <span class="text-xs text-zinc-600 font-medium">Ingat Saya</span>
                    </label>
                </div>

                <x-ui.button type="submit" variant="primary" class="w-full mt-2">
                    Masuk ke Sistem
                </x-ui.button>
            </form>

            <!-- Quick Demo Accounts Helper -->
            <div class="mt-6 pt-5 border-t border-zinc-200">
                <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider text-center mb-3">
                    Akses Akun Demo Cepat
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <x-ui.button type="button" variant="outline" size="sm" onclick="fillLogin('admin', 'password')">Admin</x-ui.button>
                    <x-ui.button type="button" variant="outline" size="sm" onclick="fillLogin('petugas', 'password')">Petugas</x-ui.button>
                    <x-ui.button type="button" variant="outline" size="sm" onclick="fillLogin('siswa', 'password')">Siswa</x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <p class="text-center text-xs text-zinc-500 mt-6">
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