@extends('layouts.app')

@section('title', 'Profil Pengguna')
@section('header_title', 'Profil Pengguna')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Breadcrumb / Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-zinc-900 tracking-tight">
                Pengaturan Profil Saya
            </h1>
            <p class="text-xs sm:text-sm text-zinc-500 mt-0.5">
                Kelola informasi akun, identitas, dan keamanan kata sandi Anda
            </p>
        </div>
        @if($user->isAdmin())
            <a href="{{ route('sekolah.edit') }}" class="px-4 py-2 rounded-xl border border-zinc-300 hover:bg-zinc-50 text-zinc-700 font-semibold text-xs transition inline-flex items-center gap-1.5">
                <svg class="w-4 h-4 text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Buka Profil Sekolah &rarr;
            </a>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- User Identity Card (Left) -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl border border-zinc-200 shadow-xs p-6 text-center">
                <div class="w-20 h-20 rounded-full mx-auto bg-gradient-to-tr from-indigo-600 to-blue-500 text-white font-black text-2xl flex items-center justify-center shadow-md border-2 border-white">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>

                <h2 class="text-lg font-bold text-zinc-900 mt-3.5">
                    {{ $user->name }}
                </h2>
                <p class="font-mono text-xs text-zinc-500 mt-0.5">
                    {{ '@' . $user->username }}
                </p>

                <div class="mt-3">
                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full {{ $user->role->badgeClass() }}">
                        {{ $user->role->label() }}
                    </span>
                </div>

                <div class="mt-6 pt-5 border-t border-zinc-200 text-left text-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500">Email</span>
                        <span class="font-medium text-zinc-800">{{ $user->email ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500">Terdaftar Sejak</span>
                        <span class="font-medium text-zinc-800">{{ $user->created_at?->translatedFormat('d F Y') ?? '-' }}</span>
                    </div>

                    @if($user->isSiswa() && $user->siswa)
                        <div class="my-2 border-t border-dashed border-zinc-200"></div>
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">NISN / NIS</span>
                            <span class="font-mono font-semibold text-zinc-800">{{ $user->siswa->nisn }} / {{ $user->siswa->nis }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Kelas</span>
                            <span class="font-medium text-zinc-800">{{ $user->siswa->kelas?->nama_kelas ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-zinc-500">Jurusan</span>
                            <span class="font-medium text-zinc-800">{{ $user->siswa->kelas?->jurusan?->nama ?? '-' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($user->isAdmin())
                <!-- Quick Settings Link Card for Admin -->
                <div class="p-4 rounded-xl bg-zinc-100 border border-zinc-300 text-xs space-y-2">
                    <div class="font-bold text-indigo-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-zinc-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pengaturan Lembaga Sekolah
                    </div>
                    <p class="text-indigo-800 leading-relaxed">
                        Pengaturan Kop Surat, Nomor Telepon, Nama Kepala Sekolah, dan Alamat Sekolah dapat dikonfigurasi melalui menu <strong>Profil Sekolah</strong>.
                    </p>
                    <a href="{{ route('sekolah.edit') }}" class="inline-flex items-center gap-1 font-semibold text-zinc-900 hover:underline pt-1">
                        Kelola Profil Sekolah &rarr;
                    </a>
                </div>
            @endif
        </div>

        <!-- Edit Forms (Right) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Form 1: Ubah Data Akun -->
            <div class="bg-white rounded-xl border border-zinc-200 shadow-xs p-6 sm:p-7 space-y-5">
                <div class="border-b border-zinc-200 pb-4">
                    <h2 class="text-base font-bold text-zinc-900">
                        Informasi Dasar Akun
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Perbarui nama tampilan dan alamat email Anda
                    </p>
                </div>

                <form method="POST" action="{{ route('profil.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-zinc-700">
                                Username (ID Login)
                            </label>
                            <input type="text" value="{{ $user->username }}" disabled class="w-full px-3.5 py-2 text-xs bg-zinc-100 rounded-xl border border-zinc-300 opacity-75 cursor-not-allowed font-mono">
                            <span class="text-[10px] text-zinc-500">Username tidak dapat diubah sendiri.</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-zinc-700">
                                Hak Akses (Role)
                            </label>
                            <input type="text" value="{{ $user->role->label() }}" disabled class="w-full px-3.5 py-2 text-xs bg-zinc-100 rounded-xl border border-zinc-300 opacity-75 cursor-not-allowed font-semibold">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="name" class="block text-xs font-semibold text-zinc-700">
                            Nama Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-3.5 py-2 rounded-xl border border-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-400 text-xs" placeholder="Nama lengkap Anda">
                    </div>

                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-semibold text-zinc-700">
                            Alamat Email
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="w-full px-3.5 py-2 rounded-xl border border-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-400 text-xs" placeholder="nama@sekolah.sch.id">
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white text-xs font-bold transition shadow-sm">
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>

            <!-- Form 2: Ganti Password -->
            <div class="bg-white rounded-xl border border-zinc-200 shadow-xs p-6 sm:p-7 space-y-5">
                <div class="border-b border-zinc-200 pb-4">
                    <h2 class="text-base font-bold text-zinc-900">
                        Perbarui Kata Sandi
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Pastikan menggunakan kombinasi kata sandi yang kuat dan aman
                    </p>
                </div>

                <form method="POST" action="{{ route('profil.password') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="space-y-1.5">
                        <label for="current_password" class="block text-xs font-semibold text-zinc-700">
                            Kata Sandi Saat Ini <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="current_password" name="current_password" required class="w-full px-3.5 py-2 rounded-xl border border-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-400 text-xs" placeholder="Ketik kata sandi lama Anda">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label for="password" class="block text-xs font-semibold text-zinc-700">
                                Kata Sandi Baru <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" id="password" name="password" required class="w-full px-3.5 py-2 rounded-xl border border-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-400 text-xs" placeholder="Minimal 6 karakter">
                        </div>

                        <div class="space-y-1.5">
                            <label for="password_confirmation" class="block text-xs font-semibold text-zinc-700">
                                Konfirmasi Kata Sandi Baru <span class="text-rose-500">*</span>
                            </label>
                            <input type="password" id="password_confirmation" name="password_confirmation" required class="w-full px-3.5 py-2 rounded-xl border border-zinc-300 focus:outline-none focus:ring-2 focus:ring-zinc-400 text-xs" placeholder="Ulangi kata sandi baru">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white text-xs font-bold transition shadow-sm">
                            Perbarui Kata Sandi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
