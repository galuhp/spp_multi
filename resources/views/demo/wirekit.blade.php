<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo WireKit - E-SPP</title>
    @vite('resources/css/demo-wirekit.css')
    @wirekitStyles
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10">
        <header>
            <p class="text-xs font-semibold uppercase tracking-wider text-violet-600">Demo UI Kit</p>
            <h1 class="mt-1 text-2xl font-bold">WireKit</h1>
            <p class="mt-1 text-sm text-slate-500">195+ komponen Blade untuk TALL stack. <a href="https://www.wirekit.app" class="text-violet-600 underline" target="_blank">wirekit.app</a></p>
        </header>

        <x-wirekit::card>
            <h2 class="mb-4 text-lg font-semibold">Tombol</h2>
            <div class="flex flex-wrap gap-3">
                <x-wirekit::button>Tombol Utama</x-wirekit::button>
                <x-wirekit::button intent="neutral" surface="outline">Sekunder</x-wirekit::button>
                <x-wirekit::button intent="success">Simpan</x-wirekit::button>
                <x-wirekit::button intent="danger">Hapus</x-wirekit::button>
                <x-wirekit::button intent="info" surface="soft">Info</x-wirekit::button>
            </div>
        </x-wirekit::card>

        <x-wirekit::card>
            <h2 class="mb-4 text-lg font-semibold">Form</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-wirekit::input label="Nama Siswa" placeholder="Budi Santoso" />
                <x-wirekit::input label="NISN" placeholder="0012345678" />
                <x-wirekit::select label="Kelas" placeholder="Pilih kelas">
                    <option value="x-rpl-1">X RPL 1</option>
                    <option value="xi-rpl-1">XI RPL 1</option>
                    <option value="xii-rpl-1">XII RPL 1</option>
                </x-wirekit::select>
                <x-wirekit::input label="Tanggal Bayar" type="date" />
            </div>
            <div class="mt-4">
                <x-wirekit::toggle label="Kirim notifikasi ke wali" />
            </div>
        </x-wirekit::card>

        <x-wirekit::card>
            <h2 class="mb-4 text-lg font-semibold">Alert & Badge</h2>
            <div class="space-y-3">
                <x-wirekit::alert intent="info" title="Informasi">Pembayaran berhasil diverifikasi.</x-wirekit::alert>
                <x-wirekit::alert intent="success" title="Berhasil">Data siswa tersimpan.</x-wirekit::alert>
                <x-wirekit::alert intent="warning" title="Peringatan">NISN belum diisi.</x-wirekit::alert>
                <x-wirekit::alert intent="danger" title="Error">Kelas tidak ditemukan pada tahun ajaran tujuan.</x-wirekit::alert>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-wirekit::badge intent="success">Lunas</x-wirekit::badge>
                <x-wirekit::badge intent="warning">Menunggu</x-wirekit::badge>
                <x-wirekit::badge intent="danger">Belum Lunas</x-wirekit::badge>
            </div>
        </x-wirekit::card>

        <x-wirekit::card>
            <h2 class="mb-4 text-lg font-semibold">Statistik</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <x-wirekit::stat label="Total Siswa" value="320" />
                <x-wirekit::stat label="Lunas" value="280" />
                <x-wirekit::stat label="Belum Lunas" value="40" />
                <x-wirekit::stat label="Rombel" value="12" />
            </div>
        </x-wirekit::card>

        <p class="text-center text-xs text-slate-400">Demo sementara — <a href="{{ route('login') }}" class="underline">kembali ke login</a></p>
    </div>

    @wirekitScripts
</body>
</html>