<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="fi-section rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Payment Gateway Aktif</div>
            <div class="mt-2 text-2xl font-bold tabular-nums text-zinc-900 dark:text-white">
                {{ number_format($this->activeGatewaysCount, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-zinc-500">Konfigurasi gateway aktif lintas sekolah</div>
        </div>

        <div class="fi-section rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Order Bermasalah (7 Hari)</div>
            <div class="mt-2 text-2xl font-bold tabular-nums {{ $this->problemOrdersCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ number_format($this->problemOrdersCount, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-zinc-500">Failed, expired, atau pending &gt; 24 jam</div>
        </div>

        <div class="fi-section rounded-xl border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Failed Jobs (Antrean Gagal)</div>
            <div class="mt-2 text-2xl font-bold tabular-nums {{ $this->failedJobsCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                {{ number_format($this->failedJobsCount, 0, ',', '.') }}
            </div>
            <div class="mt-1 text-xs text-zinc-500">Total job gagal di tabel failed_jobs</div>
        </div>
    </div>

    <div class="fi-section mt-6 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Status Payment Gateway Sekolah</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Sekolah</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3">Mode</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Diperbarui</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->gateways as $gateway)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $gateway->tenant?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $gateway->providerLabel() }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $gateway->is_production ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                    {{ $gateway->is_production ? 'Production' : 'Sandbox' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $gateway->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                    {{ $gateway->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $gateway->updated_at?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-zinc-500">Belum ada konfigurasi payment gateway.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="fi-section mt-6 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Order Pembayaran Online Gagal / Expired / Pending &gt; 24 Jam</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Sekolah</th>
                        <th class="px-4 py-3">Siswa</th>
                        <th class="px-4 py-3">Provider</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Dibuat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->problemOrders as $order)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $order->order_number }}</td>
                            <td class="px-4 py-3">{{ $order->tenant?->name ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $order->siswa?->nama ?? '-' }}</td>
                            <td class="px-4 py-3 uppercase">{{ $order->provider }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">Rp {{ number_format((int) $order->total_amount, 0, ',', '.') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $order->status === 'failed' ? 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300' }}">
                                    {{ strtoupper($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $order->created_at?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-zinc-500">Tidak ada order pembayaran bermasalah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="fi-section mt-6 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Failed Jobs Terbaru</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-xs uppercase text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Queue</th>
                        <th class="px-4 py-3">Failed At</th>
                        <th class="px-4 py-3">Ringkasan Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->failedJobs as $job)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">#{{ $job->id }}</td>
                            <td class="px-4 py-3">{{ $job->queue }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ $job->failed_at }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-rose-600 dark:text-rose-400">
                                {{ \Illuminate\Support\Str::limit(strtok((string) $job->exception, "\n") ?: (string) $job->exception, 140) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-zinc-500">Tidak ada failed jobs.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
