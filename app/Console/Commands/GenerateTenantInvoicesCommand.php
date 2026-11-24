<?php

namespace App\Console\Commands;

use App\Models\Pembayaran;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateTenantInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:generate-invoices {--period= : Periode tagihan format YYYY-MM (default: bulan lalu)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate tagihan bulanan untuk masing-masing tenant berdasarkan jumlah transaksi';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $periodInput = $this->option('period');
        $date = $periodInput
            ? Carbon::createFromFormat('Y-m', (string) $periodInput)->startOfMonth()
            : now()->subMonth()->startOfMonth();

        $period = $date->format('Y-m');
        $dueDate = $date->copy()->addMonth()->day(10);

        $tenants = Tenant::query()->where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            $existing = TenantInvoice::query()
                ->where('tenant_id', $tenant->id)
                ->where('period', $period)
                ->first();

            if ($existing?->isPaid()) {
                continue;
            }

            $transactionCount = Pembayaran::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();

            $fee = (int) $tenant->fee_per_transaction;
            $totalAmount = $transactionCount * $fee;

            TenantInvoice::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'period' => $period,
                ],
                [
                    'transaction_count' => $transactionCount,
                    'fee_per_transaction' => $fee,
                    'total_amount' => $totalAmount,
                    'status' => $totalAmount === 0 ? 'paid' : 'unpaid',
                    'due_date' => $dueDate,
                    'paid_at' => $totalAmount === 0 ? now() : null,
                ]
            );
        }

        $this->info("Tagihan periode {$period} berhasil dibuat untuk {$tenants->count()} tenant.");

        return self::SUCCESS;
    }
}
