<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Sekolah Baru — E-SPP</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 py-10 px-4 text-slate-800">
    <div class="mx-auto max-w-xl rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Pendaftaran Sekolah Baru</h1>
            <p class="mt-1 text-sm text-slate-600">
                Daftarkan sekolah Anda untuk menggunakan sistem E-SPP Multi-Tenant. Link verifikasi akan dikirim ke email admin.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('school.register.submit') }}" class="space-y-5">
            @csrf

            <div class="border-b border-slate-200 pb-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Data Sekolah</h2>
                <div class="mt-3 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nama Sekolah *</label>
                        <input type="text" name="nama_sekolah" value="{{ old('nama_sekolah') }}" required
                            placeholder="Contoh: SMK Negeri 1 Bandung"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Jenjang *</label>
                            <select name="jenjang" required
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                                @foreach (['SD', 'SMP', 'SMA', 'SMK'] as $jenjang)
                                    <option value="{{ $jenjang }}" @selected(old('jenjang', 'SMK') === $jenjang)>{{ $jenjang }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">NPSN (Opsional)</label>
                            <input type="text" name="npsn" value="{{ old('npsn') }}"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Kota *</label>
                            <input type="text" name="kota" value="{{ old('kota') }}" required
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">No. Telepon Sekolah</label>
                            <input type="text" name="no_telp" value="{{ old('no_telp') }}"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Data Akun Admin</h2>
                <div class="mt-3 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nama Lengkap Admin *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Email Admin *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Password *</label>
                            <input type="password" name="password" required minlength="8"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Konfirmasi Password *</label>
                            <input type="password" name="password_confirmation" required minlength="8"
                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit"
                class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 focus:outline-none">
                Daftar &amp; Kirim Link Verifikasi
            </button>
        </form>
    </div>
</body>
</html>
