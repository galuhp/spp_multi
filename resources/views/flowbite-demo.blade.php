<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pratinjau Flowbite - E-SPP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gray-50 text-gray-900 antialiased">
    <div class="mx-auto max-w-5xl px-4 py-10 space-y-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Pratinjau Komponen Flowbite</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Halaman sementara untuk mengevaluasi Flowbite pada aplikasi SPP.
                </p>
            </div>
            <a href="{{ route('login') }}"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200">
                Kembali ke Login
            </a>
        </header>

        {{-- Tombol --}}
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Tombol</h2>
            <div class="flex flex-wrap gap-3">
                <button type="button" class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                    Tombol Utama
                </button>
                <button type="button" class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200">
                    Tombol Sekunder
                </button>
                <button type="button" class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-300">
                    Simpan
                </button>
                <button type="button" class="rounded-lg bg-rose-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-rose-800 focus:outline-none focus:ring-4 focus:ring-rose-300">
                    Hapus
                </button>
            </div>
        </section>

        {{-- Form --}}
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Form</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="demo-nama" class="mb-2 block text-sm font-medium text-gray-900">Nama Siswa</label>
                    <input type="text" id="demo-nama" placeholder="Contoh: Budi Santoso"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="demo-kelas" class="mb-2 block text-sm font-medium text-gray-900">Kelas</label>
                    <select id="demo-kelas"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                        <option>X RPL 1</option>
                        <option>XI RPL 1</option>
                        <option>XII RPL 1</option>
                    </select>
                </div>
                <div>
                    <label for="demo-tanggal" class="mb-2 block text-sm font-medium text-gray-900">Tanggal Bayar</label>
                    <input type="date" id="demo-tanggal"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-900">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 bg-gray-100 text-blue-600 focus:ring-2 focus:ring-blue-500">
                        Kirim notifikasi ke wali
                    </label>
                </div>
            </div>
        </section>

        {{-- Alert & Badge --}}
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Alert & Badge</h2>
            <div class="space-y-3">
                <div class="rounded-lg bg-blue-50 p-4 text-sm text-blue-800" role="alert">
                    Informasi: pembayaran berhasil diverifikasi.
                </div>
                <div class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800" role="alert">
                    Berhasil: data siswa tersimpan.
                </div>
                <div class="rounded-lg bg-amber-50 p-4 text-sm text-amber-800" role="alert">
                    Peringatan: NISN belum diisi.
                </div>
                <div class="rounded-lg bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                    Error: kelas tidak ditemukan pada tahun ajaran tujuan.
                </div>
                <div class="flex flex-wrap gap-2 pt-2">
                    <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Lunas</span>
                    <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Menunggu</span>
                    <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">Belum Lunas</span>
                </div>
            </div>
        </section>

        {{-- Tabel --}}
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900">Tabel Pembayaran</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-500">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3">Siswa</th>
                            <th scope="col" class="px-6 py-3">Kelas</th>
                            <th scope="col" class="px-6 py-3">Periode</th>
                            <th scope="col" class="px-6 py-3">Nominal</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-200 bg-white hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Budi Santoso</td>
                            <td class="px-6 py-4">XII RPL 1</td>
                            <td class="px-6 py-4">Juli 2026</td>
                            <td class="px-6 py-4">Rp 250.000</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Lunas</span>
                            </td>
                        </tr>
                        <tr class="border-b border-gray-200 bg-white hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">Siti Aminah</td>
                            <td class="px-6 py-4">XI RPL 2</td>
                            <td class="px-6 py-4">Agustus 2026</td>
                            <td class="px-6 py-4">Rp 250.000</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800">Belum Lunas</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Modal --}}
        <section class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Modal</h2>
            <button type="button" data-modal-target="demo-modal" data-modal-toggle="demo-modal"
                class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                Buka Modal
            </button>

            <div id="demo-modal" tabindex="-1" aria-hidden="true"
                class="fixed inset-0 z-50 hidden h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden bg-black/50 p-4">
                <div class="relative w-full max-w-md rounded-lg bg-white shadow">
                    <div class="flex items-center justify-between border-b border-gray-200 p-4">
                        <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Pembayaran</h3>
                        <button type="button" data-modal-hide="demo-modal"
                            class="ml-auto inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="sr-only">Tutup</span>
                        </button>
                    </div>
                    <div class="p-6 text-sm text-gray-500">
                        Simpan pembayaran SPP Juli 2026 sebesar Rp 250.000?
                    </div>
                    <div class="flex items-center gap-2 border-t border-gray-200 p-4">
                        <button type="button"
                            class="rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                            Ya, simpan
                        </button>
                        <button type="button" data-modal-hide="demo-modal"
                            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <p class="text-center text-xs text-gray-400">
            Halaman pratinjau sementara. Hapus route <code>/flowbite-demo</code> setelah evaluasi.
        </p>
    </div>
</body>
</html>