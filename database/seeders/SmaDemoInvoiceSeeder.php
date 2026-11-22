<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantInvoice;
use Illuminate\Database\Seeder;

class SmaDemoInvoiceSeeder extends Seeder
{
    /**
     * Seed 1 tagihan uji coba (unpaid) pada tenant SMA Demo (atau tenant pertama).
     */
    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('slug', 'sma-demo')
            ->orWhere('name', 'like', '%SMA Demo%')
            ->orderBy('id')
            ->first()
            ?? Tenant::query()->orderBy('id')->first();

        if (! $tenant) {
            $this->command?->error('Tenant tidak ditemukan. Buat tenant terlebih dahulu.');

            return;
        }

        $period = now()->format('Y-m');

        $invoice = TenantInvoice::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'period' => $period,
            ],
            [
                'transaction_count' => 10,
                'fee_per_transaction' => 1000,
                'total_amount' => 10000,
                'status' => 'unpaid',
                'due_date' => now()->addDays(7)->toDateString(),
                'paid_at' => null,
                'notes' => 'Tagihan uji coba pembayaran DOKU',
            ]
        );

        $this->command?->info("Berhasil membuat tagihan uji coba #{$invoice->id} (periode {$period}, Rp 10.000) untuk tenant: {$tenant->name} ({$tenant->slug}).");
    }
}
