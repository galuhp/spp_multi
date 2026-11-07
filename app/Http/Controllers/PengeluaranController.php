<?php

namespace App\Http\Controllers;

use App\Models\PengeluaranSekolah;
use App\Models\ProfilSekolah;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    /**
     * Printable voucher/receipt for school operational expenditure.
     */
    public function cetak(PengeluaranSekolah $pengeluaran): View
    {
        abort_unless(auth()->user()?->isStaff(), 403);

        $pengeluaran->loadMissing(['user', 'tahunAjaran']);

        return view('transaksi.pengeluaran-cetak', [
            'pengeluaran' => $pengeluaran,
            'sekolah' => ProfilSekolah::current(),
        ]);
    }

    /**
     * Printable summary report for school operational expenditures.
     */
    public function cetakRekap(Request $request): View
    {
        abort_unless(auth()->user()?->isStaff(), 403);

        $tglAwal = $request->query('tgl_awal', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tglAkhir = $request->query('tgl_akhir', Carbon::now()->format('Y-m-d'));
        $kategori = $request->query('kategori');
        $metode = $request->query('metode_pembayaran');
        $tahunAjaranId = $request->query('tahun_ajaran_id');

        $pengeluarans = PengeluaranSekolah::with(['user', 'tahunAjaran'])
            ->when($tglAwal, fn ($q, $tgl) => $q->whereDate('tanggal', '>=', $tgl))
            ->when($tglAkhir, fn ($q, $tgl) => $q->whereDate('tanggal', '<=', $tgl))
            ->when($kategori, fn ($q, $k) => $q->where('kategori', $k))
            ->when($metode, fn ($q, $m) => $q->where('metode_pembayaran', $m))
            ->when($tahunAjaranId, fn ($q, $id) => $q->where('tahun_ajaran_id', $id))
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $totalPengeluaran = (int) $pengeluarans->sum('nominal');

        return view('transaksi.pengeluaran-rekap-cetak', [
            'pengeluarans' => $pengeluarans,
            'totalPengeluaran' => $totalPengeluaran,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
            'kategori' => $kategori,
            'metode' => $metode,
            'sekolah' => ProfilSekolah::current(),
        ]);
    }
}
