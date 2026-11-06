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
            ->whereNull('void_at')
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
        $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);
        $activeTahunAjaran = $siswa->spp?->tahun_ajaran;

        $riwayatPembayaran = $siswa->pembayarans()
            ->whereNull('void_at')
            ->with(['user', 'spp'])
            ->get();

        $bulanStatus = [];
        $totalLunas = 0;
        $totalBelumLunas = 0;

        $nominalSpp = (int) ($siswa->spp?->nominal ?? 0);

        foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
            $bulan = $periode['bulan'];
            $tahun = $periode['tahun'];

            $bayarList = $riwayatPembayaran
                ->where('spp_id', $siswa->spp_id)
                ->where('bulan_dibayar', $bulan);

            $dibayar = (int) $bayarList->sum('jumlah_bayar');
            $sisa = max(0, $nominalSpp - $dibayar);
            $status = $dibayar <= 0 ? 'belum' : ($sisa > 0 ? 'sebagian' : 'lunas');

            $totalLunas += min($dibayar, $nominalSpp);
            $totalBelumLunas += $sisa;

            $bulanStatus[] = [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'nominal' => $nominalSpp,
                'dibayar' => min($dibayar, $nominalSpp),
                'sisa' => $sisa,
                'status' => $status,
                'lunas' => $status === 'lunas',
                'pembayaran' => $bayarList->sortByDesc('id')->first(),
            ];
        }

        // Non-SPP Tariffs status for this student
        $tingkat = Spp::normalisasiTingkat($siswa->kelas?->tingkat);
        $variasiTingkat = array_values(array_unique(array_filter([
            $tingkat,
            $tingkat ? (Spp::TINGKAT_LIST[$tingkat] ?? null) : null,
        ])));

        $nonSppTarifs = Spp::where('kategori', '!=', Spp::KATEGORI_SPP)
            ->where(function ($q) use ($tahunMulai, $activeTahunAjaran) {
                $q->whereIn('tahun', [$tahunMulai, $tahunMulai + 1])
                    ->orWhere('tahun_ajaran', $activeTahunAjaran);
            })
            ->where(function ($q) use ($variasiTingkat): void {
                $q->whereNull('tingkat');

                if ($variasiTingkat !== []) {
                    $q->orWhereIn('tingkat', $variasiTingkat);
                }
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
            'activeYear' => $tahunMulai,
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

        abort_if($pembayaran->isVoid(), 403, 'Kuitansi ini sudah dibatalkan.');

        $pembayaran->load(['siswa.kelas.jurusan', 'user', 'spp']);

        return view('pembayaran.cetak', [
            'pembayaran' => $pembayaran,
            'relatedPayments' => $pembayaran->relatedBatchPayments(),
        ]);
    }
}
