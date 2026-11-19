<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Pembayaran;
use App\Models\Tenant;
use App\Models\TenantInvoice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SuperAdminStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $activeTenants = Tenant::query()->where('is_active', true)->count();
        $totalTenants = Tenant::query()->count();

        $monthlyTx = Pembayaran::withoutGlobalScopes()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $paidRevenue = (int) TenantInvoice::query()
            ->where('status', 'paid')
            ->sum('total_amount');

        $unpaidAmount = (int) TenantInvoice::query()
            ->where('status', '!=', 'paid')
            ->sum('total_amount');

        $unpaidCount = TenantInvoice::query()
            ->where('status', '!=', 'paid')
            ->where('total_amount', '>', 0)
            ->count();

        return [
            Stat::make('Sekolah Aktif', "{$activeTenants} / {$totalTenants}")
                ->description('Tenant terdaftar & aktif')
                ->color('primary'),
            Stat::make('Transaksi Bulan Ini', number_format($monthlyTx, 0, ',', '.'))
                ->description('Periode '.now()->format('M Y'))
                ->color('info'),
            Stat::make('Pendapatan Fee (Lunas)', 'Rp '.number_format($paidRevenue, 0, ',', '.'))
                ->description('Total tagihan terbayar')
                ->color('success'),
            Stat::make('Tagihan Belum Lunas', 'Rp '.number_format($unpaidAmount, 0, ',', '.'))
                ->description("{$unpaidCount} tagihan menunggu pembayaran")
                ->color($unpaidCount > 0 ? 'warning' : 'success'),
        ];
    }
}
