<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard view depending on user role.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isSiswa()) {
            return $this->siswaDashboard($user);
        }

        return $this->staffDashboard();
    }

    /**
     * Dashboard for Admin and Petugas.
     */
    protected function staffDashboard(): View
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $totalSiswa = Siswa::count();
        $totalKelas = Kelas::count();
        $totalSpp = Spp::count();

        $hariIni = Pembayaran::query()
            ->whereDate('tgl_bayar', $today)
            ->selectRaw('COUNT(*) as transaksi, COALESCE(SUM(jumlah_bayar), 0) as nominal')
            ->first();
        $transaksiHariIni = (int) ($hariIni?->transaksi ?? 0);
        $nominalHariIni = (int) ($hariIni?->nominal ?? 0);

        $bulanIni = Pembayaran::query()
            ->where('tgl_bayar', '>=', $startOfMonth)
            ->selectRaw('COUNT(*) as transaksi, COALESCE(SUM(jumlah_bayar), 0) as nominal')
            ->first();
        $nominalBulanIni = (int) ($bulanIni?->nominal ?? 0);
        $transaksiBulanIni = (int) ($bulanIni?->transaksi ?? 0);

        $transaksiTerbaru = Pembayaran::with(['siswa.kelas', 'user', 'spp'])
            ->latest('id')
            ->take(8)
            ->get();

        return view('dashboard.staff', [
            'totalSiswa' => $totalSiswa,
            'totalKelas' => $totalKelas,
            'totalSpp' => $totalSpp,
            'transaksiHariIni' => $transaksiHariIni,
            'nominalHariIni' => $nominalHariIni,
            'nominalBulanIni' => $nominalBulanIni,
            'transaksiBulanIni' => $transaksiBulanIni,
            'transaksiTerbaru' => $transaksiTerbaru,
        ]);
    }

    /**
     * Dashboard for Siswa.
     */
    protected function siswaDashboard($user): View
    {
        $siswa = $user->siswa;

        if (! $siswa) {
            $siswa = Siswa::where('nisn', $user->username)->first();
        }

        $riwayatPembayaran = collect();
        $statusBulan = [];
        $totalDibayar = 0;

        if ($siswa) {
            $siswa->loadMissing(['kelas.jurusan', 'spp']);

            $riwayatPembayaran = $siswa->pembayarans()
                ->with(['user', 'spp'])
                ->latest('tgl_bayar')
                ->get();

            $totalDibayar = $siswa->totalDibayar();

            $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);

            foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
                $pembayaran = $riwayatPembayaran->first(function ($p) use ($periode, $siswa) {
                    return $p->spp_id === $siswa->spp_id && $p->bulan_dibayar === $periode['bulan'];
                });

                $statusBulan[] = [
                    'bulan' => $periode['bulan'],
                    'tahun' => $periode['tahun'],
                    'lunas' => $pembayaran !== null,
                    'pembayaran' => $pembayaran,
                ];
            }
        }

        return view('dashboard.siswa', [
            'siswa' => $siswa,
            'riwayatPembayaran' => $riwayatPembayaran,
            'statusBulan' => $statusBulan,
            'totalDibayar' => $totalDibayar,
        ]);
    }
}
