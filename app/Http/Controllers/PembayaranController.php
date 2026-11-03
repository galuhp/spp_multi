<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PembayaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $tglAwal = $request->query('tgl_awal');
        $tglAkhir = $request->query('tgl_akhir');

        $pembayarans = Pembayaran::with(['siswa.kelas', 'user', 'spp'])
            ->when($search, function ($query, $term) {
                return $query->where('kode_transaksi', 'like', "%{$term}%")
                    ->orWhereHas('siswa', function ($q) use ($term) {
                        $q->where('nama', 'like', "%{$term}%")
                            ->orWhere('nisn', 'like', "%{$term}%");
                    });
            })
            ->when($tglAwal, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '>=', $tgl);
            })
            ->when($tglAkhir, function ($query, $tgl) {
                return $query->whereDate('tgl_bayar', '<=', $tgl);
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalPenerimaan = Pembayaran::when($tglAwal, fn ($q) => $q->whereDate('tgl_bayar', '>=', $tglAwal))
            ->when($tglAkhir, fn ($q) => $q->whereDate('tgl_bayar', '<=', $tglAkhir))
            ->sum('jumlah_bayar');

        return view('pembayaran.index', [
            'pembayarans' => $pembayarans,
            'totalPenerimaan' => $totalPenerimaan,
            'search' => $search,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $selectedSiswaId = $request->query('siswa_id');
        $selectedSiswa = null;
        $unpaidMonths = [];

        if ($selectedSiswaId) {
            $selectedSiswa = Siswa::with(['kelas.jurusan', 'spp', 'pembayarans'])->find($selectedSiswaId);

            if ($selectedSiswa) {
                $activeYear = $selectedSiswa->spp?->tahun ?? (int) date('Y');
                $paidMonths = $selectedSiswa->pembayarans
                    ->where('tahun_dibayar', $activeYear)
                    ->pluck('bulan_dibayar')
                    ->all();

                foreach (Pembayaran::TAHUN_AJARAN_BULAN_LIST as $bulan) {
                    if (! in_array($bulan, $paidMonths, true)) {
                        $unpaidMonths[] = [
                            'bulan' => $bulan,
                            'tahun' => $activeYear,
                            'nominal' => $selectedSiswa->spp?->nominal ?? 0,
                        ];
                    }
                }
            }
        }

        $allSiswas = Siswa::with('kelas')->orderBy('nama')->get();

        return view('pembayaran.create', [
            'allSiswas' => $allSiswas,
            'selectedSiswa' => $selectedSiswa,
            'unpaidMonths' => $unpaidMonths,
            'allMonths' => Pembayaran::TAHUN_AJARAN_BULAN_LIST,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswas,id'],
            'tgl_bayar' => ['required', 'date'],
            'tahun_dibayar' => ['required', 'integer', 'min:2000', 'max:2100'],
            'bulan_dibayar' => ['required', 'array', 'min:1'],
            'bulan_dibayar.*' => ['required', 'string'],
            'metode_pembayaran' => ['required', 'string', 'max:30'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'siswa_id.required' => 'Pilih siswa yang akan melakukan pembayaran.',
            'tgl_bayar.required' => 'Tanggal pembayaran wajib diisi.',
            'tahun_dibayar.required' => 'Tahun pembayaran wajib diisi.',
            'bulan_dibayar.required' => 'Pilih minimal satu bulan SPP yang akan dibayar.',
            'bulan_dibayar.min' => 'Pilih minimal satu bulan SPP yang akan dibayar.',
            'metode_pembayaran.required' => 'Metode pembayaran wajib dipilih.',
        ]);

        $siswa = Siswa::with('spp')->findOrFail($validated['siswa_id']);
        $nominalPerBulan = $siswa->spp?->nominal ?? 0;

        if ($nominalPerBulan <= 0) {
            return back()->withInput()->with('error', 'Siswa belum memiliki tarif SPP yang valid.');
        }

        $createdPembayarans = [];

        DB::transaction(function () use ($validated, $siswa, $nominalPerBulan, &$createdPembayarans) {
            foreach ($validated['bulan_dibayar'] as $bulan) {
                // Check if already paid
                $exists = Pembayaran::where('siswa_id', $siswa->id)
                    ->where('bulan_dibayar', $bulan)
                    ->where('tahun_dibayar', $validated['tahun_dibayar'])
                    ->exists();

                if ($exists) {
                    continue; // Skip if already paid
                }

                $pembayaran = Pembayaran::create([
                    'kode_transaksi' => Pembayaran::generateKodeTransaksi(),
                    'user_id' => auth()->id(),
                    'siswa_id' => $siswa->id,
                    'spp_id' => $siswa->spp_id,
                    'tgl_bayar' => $validated['tgl_bayar'],
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => $validated['tahun_dibayar'],
                    'jumlah_bayar' => $nominalPerBulan,
                    'metode_pembayaran' => $validated['metode_pembayaran'],
                    'catatan' => $validated['catatan'] ?? ('Pembayaran SPP Bulan ' . $bulan . ' ' . $validated['tahun_dibayar']),
                ]);

                $createdPembayarans[] = $pembayaran;
            }
        });

        if (empty($createdPembayarans)) {
            return back()->withInput()->with('error', 'Bulan yang Anda pilih sudah pernah dibayar sebelumnya.');
        }

        $firstTrx = $createdPembayarans[0];

        return redirect()->route('pembayaran.show', $firstTrx->id)
            ->with('success', 'Transaksi berhasil disimpan (' . count($createdPembayarans) . ' bulan SPP). Kuitansi siap dicetak.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pembayaran $pembayaran): View
    {
        $pembayaran->load(['siswa.kelas.jurusan', 'user', 'spp']);

        // Check if there are batch payments on the same date for the same student
        $relatedPayments = Pembayaran::where('siswa_id', $pembayaran->siswa_id)
            ->whereDate('tgl_bayar', $pembayaran->tgl_bayar)
            ->where('created_at', '>=', $pembayaran->created_at->subMinutes(5))
            ->where('created_at', '<=', $pembayaran->created_at->addMinutes(5))
            ->get();

        return view('pembayaran.show', [
            'pembayaran' => $pembayaran,
            'relatedPayments' => $relatedPayments,
        ]);
    }

    /**
     * Print receipt view for single or batch payment.
     */
    public function cetak(Pembayaran $pembayaran): View
    {
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pembayaran $pembayaran): RedirectResponse
    {
        $pembayaran->delete();

        return redirect()->route('pembayaran.index')->with('success', 'Transaksi pembayaran berhasil dihapus.');
    }
}
