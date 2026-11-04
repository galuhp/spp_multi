<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Preline UI - E-SPP</title>
    @vite(['resources/css/demo-preline.css', 'resources/js/demo-preline.js'])
</head>
<body class="min-h-full bg-gray-50 text-gray-900 antialiased">
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-10">
        <header>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Demo UI Kit</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">Preline UI</h1>
            <p class="mt-1 text-sm text-gray-500">Tailwind CSS components + headless plugins. <a href="https://preline.co" class="text-blue-600 underline" target="_blank">preline.co</a></p>
        </header>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Tombol</h2>
            <div class="flex flex-wrap gap-3">
                <button type="button" class="inline-flex items-center gap-x-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-100">Tombol Utama</button>
                <button type="button" class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100">Sekunder</button>
                <button type="button" class="inline-flex items-center gap-x-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">Simpan</button>
                <button type="button" class="inline-flex items-center gap-x-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700">Hapus</button>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Form</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="pl-nama" class="mb-2 block text-sm font-medium">Nama Siswa</label>
                    <input type="text" id="pl-nama" placeholder="Budi Santoso"
                        class="block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="pl-kelas" class="mb-2 block text-sm font-medium">Kelas</label>
                    <select id="pl-kelas" class="block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option>X RPL 1</option>
                        <option>XI RPL 1</option>
                        <option>XII RPL 1</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Accordion (plugin JS)</h2>
            <div class="hs-accordion-group space-y-2">
                <div class="hs-accordion active rounded-lg border border-gray-200" id="pl-accordion-1">
                    <button type="button" class="hs-accordion-toggle flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium">
                        Bagaimana cara membayar SPP?
                        <svg class="hs-accordion-active:rotate-180 size-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                    </button>
                    <div class="hs-accordion-content w-full overflow-hidden transition-[height] duration-300">
                        <p class="px-4 pb-3 text-sm text-gray-600">Pembayaran dapat dilakukan melalui petugas atau transfer, lalu unggah bukti pembayaran.</p>
                    </div>
                </div>
                <div class="hs-accordion rounded-lg border border-gray-200" id="pl-accordion-2">
                    <button type="button" class="hs-accordion-toggle flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium">
                        Apakah bisa dicicil?
                        <svg class="hs-accordion-active:rotate-180 size-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                    </button>
                    <div class="hs-accordion-content hidden w-full overflow-hidden transition-[height] duration-300">
                        <p class="px-4 pb-3 text-sm text-gray-600">Ya, pembayaran dapat dilakukan bertahap sesuai kebijakan sekolah.</p>
                    </div>
                </div>
            </div>
        </section>

        <p class="text-center text-xs text-gray-400">Demo sementara — <a href="{{ route('login') }}" class="underline">kembali ke login</a></p>
    </div>
</body>
</html>