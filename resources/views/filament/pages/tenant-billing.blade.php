<x-filament-panels::page>
    @php
        $invoices = $this->getInvoices();
        $hasUnpaid = $invoices->contains(fn ($inv) => ! $inv->isPaid() && $inv->total_amount > 0);
    @endphp

    @if ($hasUnpaid)
        <div class="rounded-xl border border-danger-300 bg-danger-50 p-4 text-danger-800 dark:border-danger-700 dark:bg-danger-950/50 dark:text-danger-200">
            <div class="font-semibold">Akses Fitur Sekolah Ditangguhkan</div>
            <p class="mt-1 text-sm">
                Sekolah Anda memiliki tagihan bulanan yang belum dilunasi. Silakan selesaikan pembayaran melalui DOKU di bawah ini untuk mengaktifkan kembali seluruh fitur.
            </p>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">Periode</th>
                    <th class="px-4 py-3">Jumlah Transaksi</th>
                    <th class="px-4 py-3">Fee / Transaksi</th>
                    <th class="px-4 py-3">Total Tagihan</th>
                    <th class="px-4 py-3">Jatuh Tempo</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($invoices as $invoice)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $invoice->period }}</td>
                        <td class="px-4 py-3">{{ number_format($invoice->transaction_count, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">Rp {{ number_format($invoice->fee_per_transaction, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 font-semibold">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $invoice->due_date?->format('d M Y') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($invoice->isPaid())
                                <span class="inline-flex items-center rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900/40 dark:text-success-300">
                                    Lunas
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-warning-100 px-2.5 py-0.5 text-xs font-medium text-warning-800 dark:bg-warning-900/40 dark:text-warning-300">
                                    Belum Dibayar
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (! $invoice->isPaid() && $invoice->total_amount > 0)
                                <x-filament::button
                                    wire:click="payWithDoku({{ $invoice->id }})"
                                    color="primary"
                                    size="sm"
                                >
                                    Bayar Sekarang (DOKU)
                                </x-filament::button>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            Belum ada tagihan untuk sekolah ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
