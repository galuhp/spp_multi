<!DOCTYPE html>
<html lang="id" class="h-full bg-base-200">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo MaryUI - E-SPP</title>
    @vite('resources/css/demo-mary.css')
    @livewireStyles
</head>
<body class="min-h-full bg-base-200 text-base-content antialiased">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10">
        <header>
            <p class="text-xs font-semibold uppercase tracking-wider text-primary">Demo UI Kit</p>
            <h1 class="mt-1 text-2xl font-bold">MaryUI</h1>
            <p class="mt-1 text-sm opacity-70">Komponen Livewire berbasis daisyUI + Tailwind. <a href="https://mary-ui.com" class="link link-primary" target="_blank">mary-ui.com</a></p>
        </header>

        <x-mary-card title="Tombol" shadow>
            <div class="flex flex-wrap gap-2">
                <x-mary-button label="Tombol Utama" class="btn-primary" />
                <x-mary-button label="Sekunder" class="btn-outline" />
                <x-mary-button label="Sukses" class="btn-success" />
                <x-mary-button label="Hapus" class="btn-error" />
            </div>
        </x-mary-card>

        <x-mary-card title="Form" shadow>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-mary-input label="Nama Siswa" placeholder="Budi Santoso" />
                <x-mary-select label="Kelas" :options="[
                    ['id' => 1, 'name' => 'X RPL 1'],
                    ['id' => 2, 'name' => 'XI RPL 1'],
                    ['id' => 3, 'name' => 'XII RPL 1'],
                ]" />
            </div>
        </x-mary-card>

        <x-mary-card title="Alert & Badge" shadow>
            <div class="space-y-3">
                <x-mary-alert title="Informasi" description="Pembayaran berhasil diverifikasi." icon="o-information-circle" class="alert-info" />
                <x-mary-alert title="Berhasil" description="Data siswa tersimpan." icon="o-check-circle" class="alert-success" />
                <x-mary-alert title="Peringatan" description="NISN belum diisi." icon="o-exclamation-triangle" class="alert-warning" />
                <x-mary-alert title="Error" description="Kelas tidak ditemukan pada tahun ajaran tujuan." icon="o-x-circle" class="alert-error" />
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-mary-badge value="Lunas" class="badge-success" />
                <x-mary-badge value="Menunggu" class="badge-warning" />
                <x-mary-badge value="Belum Lunas" class="badge-error" />
            </div>
        </x-mary-card>

        <x-mary-card title="Statistik" shadow>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-mary-stat title="Total Siswa" value="320" icon="o-users" />
                <x-mary-stat title="Lunas" value="280" icon="o-check-badge" />
                <x-mary-stat title="Belum Lunas" value="40" icon="o-exclamation-triangle" />
            </div>
        </x-mary-card>

        <p class="text-center text-xs opacity-60">Demo sementara — <a href="{{ route('login') }}" class="link">kembali ke login</a></p>
    </div>

    @livewireScripts
</body>
</html>