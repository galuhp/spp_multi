<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo WireUI - E-SPP</title>
    @vite('resources/css/demo-wireui.css')
    @wireUiStyles
</head>
<body class="min-h-full bg-gray-50 text-gray-900 antialiased">
    <div class="mx-auto max-w-5xl space-y-6 px-4 py-10">
        <header>
            <p class="text-xs font-semibold uppercase tracking-wider text-sky-600">Demo UI Kit</p>
            <h1 class="mt-1 text-2xl font-bold">WireUI</h1>
            <p class="mt-1 text-sm text-gray-500">Komponen Laravel + Livewire + Tailwind. <a href="https://wireui.dev" class="text-sky-600 underline" target="_blank">wireui.dev</a></p>
        </header>

        <x-card title="Tombol">
            <div class="flex flex-wrap gap-3">
                <x-button label="Tombol Utama" primary />
                <x-button label="Sekunder" />
                <x-button label="Sukses" positive />
                <x-button label="Hapus" negative />
                <x-button label="Outline" outline />
                <x-button label="Flat" flat />
            </div>
        </x-card>

        <x-card title="Form">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-input label="Nama Siswa" placeholder="Budi Santoso" icon="user" />
                <x-input label="NISN" placeholder="0012345678" icon="identification" />
                <x-select label="Kelas" placeholder="Pilih kelas" option-label="label" option-value="value" :options="[
                    ['label' => 'X RPL 1', 'value' => 'x-rpl-1'],
                    ['label' => 'XI RPL 1', 'value' => 'xi-rpl-1'],
                    ['label' => 'XII RPL 1', 'value' => 'xii-rpl-1'],
                ]" />
                <x-input label="Tanggal Bayar" type="date" />
            </div>
        </x-card>

        <x-card title="Alert & Badge">
            <div class="space-y-3">
                <x-alert title="Informasi" description="Pembayaran berhasil diverifikasi." icon="information-circle" />
                <x-alert title="Berhasil" description="Data siswa tersimpan." icon="check-circle" positive />
                <x-alert title="Peringatan" description="NISN belum diisi." icon="exclamation-triangle" warning />
                <x-alert title="Error" description="Kelas tidak ditemukan pada tahun ajaran tujuan." icon="x-circle" negative />
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <x-badge label="Lunas" positive />
                <x-badge label="Menunggu" warning />
                <x-badge label="Belum Lunas" negative />
            </div>
        </x-card>

        <x-card title="Toggle & Modal">
            <div class="flex flex-col gap-4">
                <x-toggle label="Kirim notifikasi ke wali" />
                <div>
                    <x-button label="Buka Modal" primary x-on:click="$openModal('demoWireuiModal')" />
                    <x-modal name="demoWireuiModal" title="Konfirmasi Pembayaran">
                        <p class="text-sm text-gray-600">Simpan pembayaran SPP Juli 2026 sebesar Rp 250.000?</p>
                        <x-slot name="footer">
                            <div class="flex justify-end gap-2">
                                <x-button label="Batal" flat x-on:click="close" />
                                <x-button label="Ya, Simpan" primary x-on:click="close" />
                            </div>
                        </x-slot>
                    </x-modal>
                </div>
            </div>
        </x-card>

        <p class="text-center text-xs text-gray-400">Demo sementara — <a href="{{ route('login') }}" class="underline">kembali ke login</a></p>
    </div>

    @wireUiScripts
</body>
</html>