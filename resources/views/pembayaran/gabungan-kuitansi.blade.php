<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi Gabungan {{ $gabungan->kode }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; background: #fff !important; }
            .kuitansi { page-break-after: always; box-shadow: none !important; border: 0 !important; }
            .kuitansi:last-of-type { page-break-after: auto; }
            @page { margin: 1cm; size: auto; }
        }
    </style>
</head>
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-sans">
    <div class="no-print max-w-2xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('filament.admin.resources.pembayaran-gabungans.edit', $gabungan) }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Gabungan
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-zinc-900 hover:bg-zinc-800 text-white rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
            Cetak Semua Kuitansi
        </button>
    </div>

    @foreach($groups as $group)
        @php
            $pembayaran = $group['payments']->first();
            $relatedPayments = $group['payments'];
        @endphp
        @include('pembayaran.partials.kuitansi', [
            'pembayaran' => $pembayaran,
            'relatedPayments' => $relatedPayments,
            'sekolah' => $sekolah,
        ])
    @endforeach
</body>
</html>
