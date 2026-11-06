<?php

namespace App\Filament\Widgets;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user?->isSiswa()) {
            $siswa = ($user->relationLoaded('siswa') ? $user->siswa : $user->siswa()->with('spp')->first())
                ?? Siswa::with('spp')->where('nisn', $user->username)->first();
            $siswa?->loadMissing('spp');
            $totalDibayar = $siswa ? $siswa->totalDibayar() : 0;
            $nominalSpp = (int) ($siswa?->spp?->nominal ?? 0);
            $bulanLunasCount = 0;

            if ($siswa && $nominalSpp > 0) {
                $bulanLunasCount = $siswa->pembayarans()
                    ->whereNull('void_at')
                    ->where('spp_id', $siswa->spp_id)
                    ->whereNotNull('bulan_dibayar')
                    ->get()
                    ->groupBy('bulan_dibayar')
                    ->filter(fn ($rows): bool => (int) $rows->sum('jumlah_bayar') >= $nominalSpp)
                    ->count();
            }

            return [
                Stat::make('Total Pembayaran Saya', 'Rp '.number_format($totalDibayar, 0, ',', '.'))
                    ->description('Akumulasi seluruh transaksi terverifikasi')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->color('success'),
                Stat::make('SPP Terbayar', $bulanLunasCount.' / 12 Bulan')
                    ->description('Tahun Pelajaran Berjalan')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color('primary'),
                Stat::make('Tarif SPP Bulanan', 'Rp '.number_format($siswa?->spp?->nominal ?? 0, 0, ',', '.'))
                    ->description($siswa?->spp?->tahun_ajaran ?? 'Tahun Aktif')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('info'),
            ];
        }

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $hariIni = Pembayaran::query()
            ->whereNull('void_at')
            ->whereDate('tgl_bayar', $today)
            ->selectRaw('COUNT(*) as transaksi, COALESCE(SUM(jumlah_bayar), 0) as nominal')
            ->first();
        $transaksiHariIni = (int) ($hariIni?->transaksi ?? 0);
        $nominalHariIni = (int) ($hariIni?->nominal ?? 0);

        $bulanIni = Pembayaran::query()
            ->whereNull('void_at')
            ->where('tgl_bayar', '>=', $startOfMonth)
            ->selectRaw('COUNT(*) as transaksi, COALESCE(SUM(jumlah_bayar), 0) as nominal')
            ->first();
        $nominalBulanIni = (int) ($bulanIni?->nominal ?? 0);
        $transaksiBulanIni = (int) ($bulanIni?->transaksi ?? 0);

        $totalSiswa = Siswa::count();
        $totalTarif = Spp::count();

        return [
            Stat::make('Penerimaan Hari Ini', 'Rp '.number_format($nominalHariIni, 0, ',', '.'))
                ->description($transaksiHariIni.' transaksi kasir hari ini')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Penerimaan Bulan Ini', 'Rp '.number_format($nominalBulanIni, 0, ',', '.'))
                ->description($transaksiBulanIni.' transaksi tercatat bulan ini')
                ->icon(Heroicon::OutlinedChartBar)
                ->color('primary'),
            Stat::make('Total Siswa Aktif', number_format($totalSiswa, 0, ',', '.').' Siswa')
                ->description('Terdaftar di rombel kelas')
                ->icon(Heroicon::OutlinedUsers)
                ->color('info'),
            Stat::make('Master Tarif & Pos', number_format($totalTarif, 0, ',', '.').' Pos Biaya')
                ->description('SPP dan pos non-SPP aktif')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color('warning'),
        ];
    }
}
