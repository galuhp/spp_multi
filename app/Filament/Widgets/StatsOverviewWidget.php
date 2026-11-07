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

        if ($user?->isWaliKelas()) {
            $kelasIds = $user->kelasWaliIds();
            $kelasNama = $user->kelasWali->pluck('nama_kelas')->implode(', ') ?: 'Belum Ditugaskan';
            $siswaQuery = Siswa::whereIn('kelas_id', $kelasIds);
            $totalSiswa = $siswaQuery->count();

            $service = app(\App\Support\Laporan\LaporanSppService::class);
            $laporan = ! empty($kelasIds) ? $service->tunggakan([
                'kelas_id' => count($kelasIds) === 1 ? (string) $kelasIds[0] : null,
            ]) : null;

            $ringkasan = $laporan['ringkasan'] ?? [
                'jumlah_menunggak' => 0,
                'tunggakan' => 0,
                'persentase' => 0,
            ];

            return [
                Stat::make('Kelas Binaan', $kelasNama)
                    ->description($totalSiswa.' total siswa di kelas Anda')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->color('primary'),
                Stat::make('Siswa Menunggak', $ringkasan['jumlah_menunggak'].' Siswa')
                    ->description('Perlu pemantauan pelunasan')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color($ringkasan['jumlah_menunggak'] > 0 ? 'danger' : 'success'),
                Stat::make('Total Tunggakan Kelas', 'Rp '.number_format($ringkasan['tunggakan'], 0, ',', '.'))
                    ->description('SPP dan pos non-SPP tertunggak')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color($ringkasan['tunggakan'] > 0 ? 'warning' : 'success'),
                Stat::make('Capaian Pembayaran', number_format($ringkasan['persentase'], 1, ',', '.').'%')
                    ->description('Tingkat pelunasan tagihan kelas')
                    ->icon(Heroicon::OutlinedCheckCircle)
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
