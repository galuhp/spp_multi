<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Exports\LaporanPembayaranExport;
use App\Exports\LaporanTunggakanExport;
use App\Exports\RekapPotensiPenerimaanExport;
use App\Filament\Pages\LaporanPembayaran;
use App\Filament\Pages\LaporanTunggakan;
use App\Filament\Pages\RekapPotensiPenerimaan;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class LaporanExcelExportTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsTenantAdmin(): User
    {
        $tenant = Tenant::create(['name' => 'SMK Export', 'slug' => 'smk-export', 'is_active' => true]);
        $admin = User::factory()->create(['is_super_admin' => false, 'role' => UserRole::Admin]);
        $tenant->users()->attach($admin->id, ['role' => UserRole::Admin->value]);

        Filament::setTenant($tenant);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_laporan_pembayaran_can_export_excel(): void
    {
        $this->actingAsTenantAdmin();
        Excel::fake();

        Livewire::test(LaporanPembayaran::class)
            ->callAction('ekspor_excel');

        Excel::assertDownloaded(
            fn (string $filename): bool => str_starts_with($filename, 'laporan-pembayaran-') && str_ends_with($filename, '.xlsx'),
            fn (LaporanPembayaranExport $export): bool => $export instanceof LaporanPembayaranExport,
        );
    }

    public function test_laporan_tunggakan_can_export_excel(): void
    {
        $this->actingAsTenantAdmin();
        Excel::fake();

        Livewire::test(LaporanTunggakan::class)
            ->callAction('ekspor_excel');

        Excel::assertDownloaded(
            fn (string $filename): bool => str_starts_with($filename, 'laporan-tunggakan-') && str_ends_with($filename, '.xlsx'),
            fn (LaporanTunggakanExport $export): bool => $export instanceof LaporanTunggakanExport,
        );
    }

    public function test_rekap_potensi_penerimaan_can_export_excel(): void
    {
        $this->actingAsTenantAdmin();
        Excel::fake();

        Livewire::test(RekapPotensiPenerimaan::class)
            ->callAction('ekspor_excel');

        Excel::assertDownloaded(
            fn (string $filename): bool => str_starts_with($filename, 'rekap-potensi-penerimaan-') && str_ends_with($filename, '.xlsx'),
            fn (RekapPotensiPenerimaanExport $export): bool => $export instanceof RekapPotensiPenerimaanExport,
        );
    }
}
