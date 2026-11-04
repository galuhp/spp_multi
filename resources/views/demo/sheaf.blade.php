<!DOCTYPE html>
<html lang="id" class="h-full bg-zinc-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Sheaf UI - E-SPP</title>
    @vite('resources/css/demo-sheaf.css')
    @livewireStyles
</head>
<body class="min-h-full bg-zinc-50 text-zinc-900 antialiased">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10">
        <header>
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Demo UI Kit</p>
            <h1 class="mt-1 text-2xl font-bold">Sheaf UI</h1>
            <p class="mt-1 text-sm text-zinc-500">
                Komponen Blade yang di-scaffold ke project (shadcn-style). <a href="https://sheafui.dev" class="underline" target="_blank">sheafui.dev</a>
            </p>
        </header>

        <x-ui.card class="!max-w-none">
            <h2 class="mb-4 text-lg font-semibold">Tombol</h2>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary">Tombol Utama</x-ui.button>
                <x-ui.button variant="secondary">Sekunder</x-ui.button>
                <x-ui.button variant="outline">Outline</x-ui.button>
                <x-ui.button variant="ghost">Ghost</x-ui.button>
                <x-ui.button variant="destructive">Hapus</x-ui.button>
                <x-ui.button variant="primary" color="green">Sukses</x-ui.button>
            </div>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-ui.button size="sm" icon="plus">Tambah</x-ui.button>
                <x-ui.button size="lg" variant="outline" icon-after="arrow-right">Lanjut</x-ui.button>
                <x-ui.button squared icon="cog-6-tooth" variant="ghost" />
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <h2 class="mb-4 text-lg font-semibold">Form</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Nama Siswa</label>
                    <x-ui.input placeholder="Budi Santoso" left-icon="user" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">NISN</label>
                    <x-ui.input placeholder="0012345678" left-icon="identification" />
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Kelas</label>
                    <x-ui.select name="kelas" placeholder="Pilih kelas">
                        <x-ui.select.option value="x-rpl-1" label="X RPL 1" />
                        <x-ui.select.option value="xi-rpl-1" label="XI RPL 1" />
                        <x-ui.select.option value="xii-rpl-1" label="XII RPL 1" />
                    </x-ui.select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium">Tanggal Bayar</label>
                    <x-ui.input type="date" />
                </div>
            </div>
            <div class="mt-4">
                <x-ui.switch label="Kirim notifikasi ke wali" />
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <h2 class="mb-4 text-lg font-semibold">Alert & Badge</h2>
            <div class="space-y-3">
                <x-ui.alerts variant="info" icon="information-circle">Pembayaran berhasil diverifikasi.</x-ui.alerts>
                <x-ui.alerts variant="success" icon="check-circle">Data siswa tersimpan.</x-ui.alerts>
                <x-ui.alerts variant="warning" icon="exclamation-triangle">NISN belum diisi.</x-ui.alerts>
                <x-ui.alerts variant="error" icon="x-circle">Kelas tidak ditemukan pada tahun ajaran tujuan.</x-ui.alerts>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.badge color="green">Lunas</x-ui.badge>
                <x-ui.badge color="yellow">Menunggu</x-ui.badge>
                <x-ui.badge color="red">Belum Lunas</x-ui.badge>
                <x-ui.badge variant="outline" color="blue">Info</x-ui.badge>
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <h2 class="mb-4 text-lg font-semibold">Tabel Pembayaran</h2>
            <x-ui.table>
                <x-ui.table.columns>
                    <x-ui.table.head>Siswa</x-ui.table.head>
                    <x-ui.table.head>Kelas</x-ui.table.head>
                    <x-ui.table.head>Periode</x-ui.table.head>
                    <x-ui.table.head>Nominal</x-ui.table.head>
                    <x-ui.table.head>Status</x-ui.table.head>
                </x-ui.table.columns>
                <x-ui.table.rows>
                    <x-ui.table.row>
                        <x-ui.table.cell class="font-medium">Budi Santoso</x-ui.table.cell>
                        <x-ui.table.cell>XII RPL 1</x-ui.table.cell>
                        <x-ui.table.cell>Juli 2026</x-ui.table.cell>
                        <x-ui.table.cell>Rp 250.000</x-ui.table.cell>
                        <x-ui.table.cell><x-ui.badge color="green">Lunas</x-ui.badge></x-ui.table.cell>
                    </x-ui.table.row>
                    <x-ui.table.row>
                        <x-ui.table.cell class="font-medium">Siti Aminah</x-ui.table.cell>
                        <x-ui.table.cell>XI RPL 2</x-ui.table.cell>
                        <x-ui.table.cell>Agustus 2026</x-ui.table.cell>
                        <x-ui.table.cell>Rp 250.000</x-ui.table.cell>
                        <x-ui.table.cell><x-ui.badge color="red">Belum Lunas</x-ui.badge></x-ui.table.cell>
                    </x-ui.table.row>
                </x-ui.table.rows>
            </x-ui.table>
        </x-ui.card>

        <p class="text-center text-xs text-zinc-400">Demo sementara — <a href="{{ route('login') }}" class="underline">kembali ke login</a></p>
    </div>

    @livewireScripts
</body>
</html>