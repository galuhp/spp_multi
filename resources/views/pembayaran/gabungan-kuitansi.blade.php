<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuitansi Gabungan {{ $gabungan->kode }}</title>
    @include('partials.app-icon')
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
<body class="bg-zinc-100 p-4 sm:p-8 text-zinc-900 antialiased font-print">
    <div class="no-print max-w-2xl mx-auto mb-4 flex items-center justify-between">
        <a href="{{ route('filament.admin.resources.pembayaran-gabungans.edit', $gabungan) }}" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900">
            &larr; Kembali ke Gabungan
        </a>
        <x-print-button onclick="window.print()">
            Cetak Semua Kuitansi
        </x-print-button>
    </div>

    @if (session('massal_info'))
        <div class="no-print max-w-2xl mx-auto mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            {{ session('massal_info') }}
        </div>
    @endif

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

    @if (request()->boolean('auto'))
        <script>
            window.addEventListener('load', function () {
                window.setTimeout(function () {
                    window.print();
                }, 400);
            });
        </script>
    @endif
</body>
</html>
