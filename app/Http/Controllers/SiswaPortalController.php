<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiswaPortalController extends Controller
{
    /**
     * Get currently logged-in student model.
     */
    protected function getLoggedInSiswa(Request $request): ?Siswa
    {
        $user = $request->user();
        if ($user->siswa) {
            return $user->siswa;
        }

        return Siswa::where('nisn', $user->username)->first();
    }

    /**
     * Display student's personal payment history.
     */
    public function riwayat(Request $request): View
    {
        $siswa = $this->getLoggedInSiswa($request);

        if (! $siswa) {
            abort(404, 'Data siswa untuk akun ini tidak ditemukan.');
        }

        $pembayarans = $siswa->pembayarans()
            ->with(['user', 'spp'])
            ->latest('tgl_bayar')
            ->paginate(15);

        return view('siswa_portal.riwayat', [
            'siswa' => $siswa,
            'pembayarans' => $pembayarans,
            'totalDibayar' => $siswa->totalDibayar(),
        ]);
    }

    /**
     * Display student's monthly billing status.
     */
    public function tagihan(Request $request): View
    {
        $siswa = $this->getLoggedInSiswa($request);

        if (! $siswa) {
            abort(404, 'Data siswa untuk akun ini tidak ditemukan.');
        }

        $siswa->load(['spp', 'kelas.jurusan']);
        $activeYear = $siswa->spp?->tahun ?? (int) date('Y');
        $activeTahunAjaran = $siswa->spp?->tahun_ajaran;

        $riwayatPembayaran = $siswa->pembayarans()
            ->with(['user', 'spp'])
            ->get();

        $bulanStatus = [];
        $totalLunas = 0;
        $totalBelumLunas = 0;

        foreach (Pembayaran::TAHUN_AJARAN_BULAN_LIST as $bulan) {
            $pembayaran = $riwayatPembayaran->first(function ($p) use ($bulan, $activeYear) {
                return $p->bulan_dibayar === $bulan && $p->tahun_dibayar === $activeYear;
            });

            $lunas = $pembayaran !== null;
            if ($lunas) {
                $totalLunas += $pembayaran->jumlah_bayar;
            } else {
                $totalBelumLunas += ($siswa->spp?->nominal ?? 0);
            }

            $bulanStatus[] = [
                'bulan' => $bulan,
                'tahun' => $activeYear,
                'nominal' => $siswa->spp?->nominal ?? 0,
                'lunas' => $lunas,
                'pembayaran' => $pembayaran,
            ];
        }

        // Non-SPP Tariffs status for this student
        $nonSppTarifs = Spp::where('kategori', '!=', Spp::KATEGORI_SPP)
            ->where(function ($q) use ($activeYear, $activeTahunAjaran) {
                $q->where('tahun', $activeYear)
                    ->orWhere('tahun_ajaran', $activeTahunAjaran);
            })
            ->get();

        $nonSppStatus = [];
        $totalNonSppLunas = 0;
        $totalNonSppBelumLunas = 0;

        foreach ($nonSppTarifs as $tarif) {
            $bayarList = $riwayatPembayaran->where('spp_id', $tarif->id);
            $dibayar = (int) $bayarList->sum('jumlah_bayar');
            $sisa = max(0, $tarif->nominal - $dibayar);
            $lunas = $sisa === 0 && $dibayar > 0;

            $totalNonSppLunas += $dibayar;
            $totalNonSppBelumLunas += $sisa;

            $nonSppStatus[] = [
                'tarif' => $tarif,
                'nominal' => $tarif->nominal,
                'dibayar' => $dibayar,
                'sisa' => $sisa,
                'lunas' => $lunas,
                'pembayarans' => $bayarList,
            ];
        }

        return view('siswa_portal.tagihan', [
            'siswa' => $siswa,
            'activeYear' => $activeYear,
            'bulanStatus' => $bulanStatus,
            'totalLunas' => $totalLunas,
            'totalBelumLunas' => $totalBelumLunas,
            'nonSppStatus' => $nonSppStatus,
            'totalNonSppLunas' => $totalNonSppLunas,
            'totalNonSppBelumLunas' => $totalNonSppBelumLunas,
        ]);
    }

    /**
     * Print student's own payment receipt.
     */
    public function cetakKuitansi(Request $request, Pembayaran $pembayaran): View
    {
        $siswa = $this->getLoggedInSiswa($request);

        if (! $siswa || $pembayaran->siswa_id !== $siswa->id) {
            abort(403, 'Anda tidak berhak mencetak kuitansi siswa lain.');
        }

        $pembayaran->load(['siswa.kelas.jurusan', 'user', 'spp']);

        return view('pembayaran.cetak', [
            'pembayaran' => $pembayaran,
            'relatedPayments' => $pembayaran->relatedBatchPayments(),
        ]);
    }
}
