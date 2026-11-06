<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Gabungan {{ $gabungan->kode }}</title>
    @include('partials.app-icon')
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; background: #fff !important; }
            @page { margin: 1cm; size: auto; }
        }
    </style>
</head>
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-sans">
    <div class="no-print max-w-4xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('filament.admin.resources.pembayaran-gabungans.edit', $gabungan) }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Gabungan
        </a>
        <x-print-button onclick="window.print()">
            Cetak Rekap
        </x-print-button>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-zinc-200">
        <div class="flex items-start justify-between border-b-2 border-black pb-4">
            <div class="flex items-center gap-3">
                @if ($sekolah->logoUrl())
                    <img src="{{ $sekolah->logoUrl() }}" alt="Logo {{ $sekolah->nama_sekolah }}"
                        class="w-12 h-12 shrink-0 rounded-xl border border-zinc-200 bg-white object-contain p-1">
                @else
                    <div class="w-12 h-12 rounded-xl bg-zinc-900 text-white flex items-center justify-center font-black text-2xl uppercase">
                        {{ substr($sekolah->nama_sekolah, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h1 class="text-base font-black tracking-wide uppercase">{{ $sekolah->nama_sekolah }}</h1>
                    <p class="text-xs text-zinc-600">{{ $sekolah->alamatLengkap() }}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] font-bold uppercase tracking-wider text-zinc-500">REKAP PEMBAYARAN GABUNGAN</div>
                <div class="font-mono font-bold text-sm text-zinc-900 mt-0.5">{{ $gabungan->kode }}</div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 py-4 text-xs border-b border-zinc-200">
            <div class="space-y-1">
                <div><span class="text-zinc-500 inline-block w-28">Tanggal</span>: <strong>{{ $gabungan->tanggal?->translatedFormat('d F Y') }}</strong></div>
                <div><span class="text-zinc-500 inline-block w-28">Sumber</span>: {{ \App\Models\PembayaranGabungan::sumberOptions()[$gabungan->sumber] ?? $gabungan->sumber }}</div>
                <div><span class="text-zinc-500 inline-block w-28">Metode</span>: {{ \App\Models\PembayaranGabungan::metodeOptions()[$gabungan->metode] ?? $gabungan->metode }}</div>
            </div>
            <div class="space-y-1 text-right">
                <div><span class="text-zinc-500">Petugas</span>: <strong>{{ $gabungan->pembuat?->name ?? '-' }}</strong></div>
                <div><span class="text-zinc-500">Diposting</span>: {{ $gabungan->diposting_at?->translatedFormat('d F Y H:i') ?? '-' }}</div>
                <div><span class="text-zinc-500">Status</span>: {{ \App\Models\PembayaranGabungan::statusOptions()[$gabungan->status] ?? $gabungan->status }}</div>
            </div>
        </div>

        <table class="w-full text-left border-collapse text-xs my-4">
            <thead>
                <tr class="border-b border-zinc-300 bg-zinc-50 font-bold uppercase text-zinc-700">
                    <th class="py-2 px-2 w-10 text-center">No</th>
                    <th class="py-2 px-2">Nama Siswa</th>
                    <th class="py-2 px-2">NISN</th>
                    <th class="py-2 px-2">Kelas</th>
                    <th class="py-2 px-2">Referensi</th>
                    <th class="py-2 px-2 text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200">
                @foreach($baris as $index => $item)
                    <tr>
                        <td class="py-2 px-2 text-center">{{ $index + 1 }}</td>
                        <td class="py-2 px-2 font-medium">{{ $item->siswa?->nama }}</td>
                        <td class="py-2 px-2 font-mono">{{ $item->siswa?->nisn ?: '-' }}</td>
                        <td class="py-2 px-2">{{ $item->siswa?->kelas?->nama_kelas ?: '-' }}</td>
                        <td class="py-2 px-2">{{ $item->referensi ?: '-' }}</td>
                        <td class="py-2 px-2 text-right font-semibold">Rp {{ number_format((int) $item->jumlah, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-black font-bold">
                    <td colspan="5" class="py-2 px-2 text-right uppercase">Total Diterima</td>
                    <td class="py-2 px-2 text-right">Rp {{ number_format((int) $gabungan->total_diterima, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="grid grid-cols-2 gap-8 items-end text-xs pt-10 mt-8 border-t border-zinc-200">
            <div class="text-center">
                <p class="text-zinc-600 mb-16">Petugas / Bendahara,</p>
                <p class="font-bold underline">{{ $gabungan->pemosting?->name ?? $gabungan->pembuat?->name }}</p>
            </div>
            <div class="text-center">
                <p class="text-zinc-600 mb-16">{{ $sekolah->kota ?? 'Bandung' }}, {{ $gabungan->tanggal?->translatedFormat('d F Y') }}<br>Kepala Sekolah,</p>
                <p class="font-bold underline">{{ $sekolah->nama_kepala_sekolah ?? '-' }}</p>
            </div>
        </div>
    </div>
</body>
</html>
