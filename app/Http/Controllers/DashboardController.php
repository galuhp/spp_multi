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

        $transaksiHariIni = Pembayaran::whereDate('tgl_bayar', $today)->count();
        $nominalHariIni = Pembayaran::whereDate('tgl_bayar', $today)->sum('jumlah_bayar');

        $nominalBulanIni = Pembayaran::where('tgl_bayar', '>=', $startOfMonth)->sum('jumlah_bayar');
        $transaksiBulanIni = Pembayaran::where('tgl_bayar', '>=', $startOfMonth)->count();

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
            $riwayatPembayaran = $siswa->pembayarans()
                ->with(['user', 'spp'])
                ->latest('tgl_bayar')
                ->get();

            $totalDibayar = $siswa->totalDibayar();

            $activeYear = $siswa->spp?->tahun ?? (int) date('Y');

            foreach (Pembayaran::TAHUN_AJARAN_BULAN_LIST as $bulan) {
                // Determine year: Juli-Desember in activeYear - 1 or activeYear
                $pembayaran = $riwayatPembayaran->first(function ($p) use ($bulan, $activeYear) {
                    return $p->bulan_dibayar === $bulan;
                });

                $statusBulan[] = [
                    'bulan' => $bulan,
                    'tahun' => $activeYear,
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
