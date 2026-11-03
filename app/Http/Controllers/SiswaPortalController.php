<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Siswa;
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

        $riwayatPembayaran = $siswa->pembayarans()
            ->with('user')
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

        return view('siswa_portal.tagihan', [
            'siswa' => $siswa,
            'activeYear' => $activeYear,
            'bulanStatus' => $bulanStatus,
            'totalLunas' => $totalLunas,
            'totalBelumLunas' => $totalBelumLunas,
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

        $relatedPayments = Pembayaran::where('siswa_id', $pembayaran->siswa_id)
            ->whereDate('tgl_bayar', $pembayaran->tgl_bayar)
            ->where('created_at', '>=', $pembayaran->created_at->subMinutes(5))
            ->where('created_at', '<=', $pembayaran->created_at->addMinutes(5))
            ->get();

        return view('pembayaran.cetak', [
            'pembayaran' => $pembayaran,
            'relatedPayments' => $relatedPayments,
        ]);
    }
}
