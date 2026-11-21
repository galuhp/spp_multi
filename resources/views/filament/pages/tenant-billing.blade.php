<x-filament-panels::page>
    @php
        $invoices = $this->getInvoices();
        $hasUnpaid = $invoices->contains(fn ($inv) => ! $inv->isPaid() && $inv->total_amount > 0);
    @endphp

    @if ($hasUnpaid)
        <div
            class="fi-section rounded-xl bg-danger-50 p-4 text-danger-800 ring-1 ring-danger-300 dark:bg-danger-950/50 dark:text-danger-200 dark:ring-danger-700"
            style="padding: 1rem; border-radius: 0.75rem;"
        >
            <div class="font-semibold">Akses Fitur Sekolah Ditangguhkan</div>
            <p class="mt-1 text-sm">
                Sekolah Anda memiliki tagihan bulanan yang belum dilunasi. Silakan selesaikan pembayaran melalui DOKU di bawah ini untuk mengaktifkan kembali seluruh fitur.
            </p>
        </div>
    @endif

    @if ($invoices->isEmpty())
        <x-filament::section>
            <div style="padding: 2.5rem 1rem; text-align: center;">
                <div
                    style="width: 3rem; height: 3rem; margin: 0 auto 0.75rem; border-radius: 9999px; background-color: rgb(241 245 249); color: rgb(100 116 139); display: flex; align-items: center; justify-content: center;"
                >
                    <x-heroicon-o-document-text width="24" height="24" style="width: 1.5rem; height: 1.5rem;" />
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                    Belum ada tagihan untuk sekolah ini.
                </p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Tagihan bulanan akan muncul otomatis di sini jika sudah diterbitkan.
                </p>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table class="w-full text-left text-sm" style="width: 100%; border-collapse: collapse; font-variant-numeric: tabular-nums;">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs uppercase text-gray-600 dark:border-white/10 dark:text-gray-300">
                            <th style="padding: 0.75rem 1rem; white-space: nowrap;">Periode</th>
                            <th style="padding: 0.75rem 1rem; white-space: nowrap;">Jumlah Transaksi</th>
                            <th style="padding: 0.75rem 1rem; white-space: nowrap;">Fee / Transaksi</th>
                            <th style="padding: 0.75rem 1rem; white-space: nowrap;">Total Tagihan</th>
                            <th style="padding: 0.75rem 1rem; white-space: nowrap;">Jatuh Tempo</th>
                            <th style="padding: 0.75rem 1rem; white-space: nowrap;">Status</th>
                            <th style="padding: 0.75rem 1rem; text-align: right; white-space: nowrap;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td style="padding: 0.75rem 1rem; white-space: nowrap;" class="font-medium text-gray-900 dark:text-white">
                                    {{ $invoice->period }}
                                </td>
                                <td style="padding: 0.75rem 1rem; white-space: nowrap;">
                                    {{ number_format($invoice->transaction_count, 0, ',', '.') }}
                                </td>
                                <td style="padding: 0.75rem 1rem; white-space: nowrap;">
                                    Rp {{ number_format($invoice->fee_per_transaction, 0, ',', '.') }}
                                </td>
                                <td style="padding: 0.75rem 1rem; white-space: nowrap;" class="font-semibold text-gray-900 dark:text-white">
                                    Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}
                                </td>
                                <td style="padding: 0.75rem 1rem; white-space: nowrap;">
                                    {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                                </td>
                                <td style="padding: 0.75rem 1rem; white-space: nowrap;">
                                    @if ($invoice->isPaid())
                                        <x-filament::badge color="success">
                                            Lunas
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning">
                                            Belum Dibayar
                                        </x-filament::badge>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem 1rem; text-align: right; white-space: nowrap;">
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
