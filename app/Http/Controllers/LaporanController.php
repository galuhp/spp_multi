<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Spp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    /**
     * Display report filter page and preview data.
     */
    public function index(Request $request): View
    {
        $tglAwal = $request->query('tgl_awal', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tglAkhir = $request->query('tgl_akhir', Carbon::now()->format('Y-m-d'));
        $kelasId = $request->query('kelas_id');
        $metode = $request->query('metode_pembayaran');
        $kategori = $request->query('kategori');

        $pembayarans = Pembayaran::with(['siswa.kelas.jurusan', 'user', 'spp', 'gabungan'])->whereNull('void_at')
            ->when($tglAwal, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '>=', $tgl);
            })
            ->when($tglAkhir, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '<=', $tgl);
            })
            ->when($kelasId, function ($query, $id) {
                return $query->whereHas('siswa', function ($q) use ($id) {
                    $q->where('kelas_id', $id);
                });
            })
            ->when($metode, function ($query, $m) {
                return $query->where('metode_pembayaran', $m);
            })
            ->when($kategori, function ($query, $kat) {
                return $query->whereHas('spp', function ($q) use ($kat) {
                    $q->where('kategori', $kat);
                });
            })
            ->orderBy('tgl_bayar', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalPenerimaan = $pembayarans->sum('jumlah_bayar');
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $kategoriList = Spp::select('kategori')->distinct()->pluck('kategori')->all();

        return view('laporan.index', [
            'pembayarans' => $pembayarans,
            'totalPenerimaan' => $totalPenerimaan,
            'kelasList' => $kelasList,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
            'kelasId' => $kelasId,
            'metode' => $metode,
            'kategori' => $kategori,
            'kategoriList' => $kategoriList,
        ]);
    }

    /**
     * Printable formal report view.
     */
    public function cetak(Request $request): View
    {
        $tglAwal = $request->query('tgl_awal', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $tglAkhir = $request->query('tgl_akhir', Carbon::now()->format('Y-m-d'));
        $kelasId = $request->query('kelas_id');
        $metode = $request->query('metode_pembayaran');
        $kategori = $request->query('kategori');

        $pembayarans = Pembayaran::with(['siswa.kelas.jurusan', 'user', 'spp', 'gabungan'])->whereNull('void_at')
            ->when($tglAwal, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '>=', $tgl);
            })
            ->when($tglAkhir, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '<=', $tgl);
            })
            ->when($kelasId, function ($query, $id) {
                return $query->whereHas('siswa', function ($q) use ($id) {
                    $q->where('kelas_id', $id);
                });
            })
            ->when($metode, function ($query, $m) {
                return $query->where('metode_pembayaran', $m);
            })
            ->when($kategori, function ($query, $kat) {
                return $query->whereHas('spp', function ($q) use ($kat) {
                    $q->where('kategori', $kat);
                });
            })
            ->orderBy('tgl_bayar', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $selectedKelas = $kelasId ? Kelas::find($kelasId) : null;
        $totalPenerimaan = $pembayarans->sum('jumlah_bayar');

        return view('laporan.cetak', [
            'pembayarans' => $pembayarans,
            'totalPenerimaan' => $totalPenerimaan,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
            'selectedKelas' => $selectedKelas,
            'metode' => $metode,
            'kategori' => $kategori,
        ]);
    }
}
