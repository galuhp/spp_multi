<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Support\Pembayaran\PembayaranService;
use App\Support\Pembayaran\TanggunganPemerintahService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $kategori = $request->query('kategori');

        $pembayarans = Pembayaran::with(['siswa.kelas', 'user', 'spp'])
            ->whereNull('void_at')
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
            ->when($kategori, function ($query, $kat) {
                return $query->whereHas('spp', function ($q) use ($kat) {
                    $q->where('kategori', $kat);
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalPenerimaan = Pembayaran::query()
            ->whereNull('void_at')
            ->when($tglAwal, fn ($q) => $q->whereDate('tgl_bayar', '>=', $tglAwal))
            ->when($tglAkhir, fn ($q) => $q->whereDate('tgl_bayar', '<=', $tglAkhir))
            ->when($kategori, fn ($q) => $q->whereHas('spp', fn ($sq) => $sq->where('kategori', $kategori)))
            ->sum('jumlah_bayar');

        $kategoriList = Spp::query()
            ->select('kategori')
            ->distinct()
            ->pluck('kategori')
            ->all();

        return view('pembayaran.index', [
            'pembayarans' => $pembayarans,
            'totalPenerimaan' => $totalPenerimaan,
            'search' => $search,
            'tglAwal' => $tglAwal,
            'tglAkhir' => $tglAkhir,
            'kategori' => $kategori,
            'kategoriList' => $kategoriList,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $selectedSiswa = null;
        $unpaidMonths = [];
        $nonSppTarifs = collect();
        $siswaId = $request->query('siswa_id');
        $kelasId = $request->query('kelas_id');
        $sertakanArsip = $request->boolean('sertakan_arsip');
        $tunggakanTahunLalu = 0;

        if ($siswaId) {
            $selectedSiswa = Siswa::with([
                'kelas.jurusan',
                'spp',
                'pembayarans' => fn ($query) => $query->whereNull('void_at')->with('spp'),
            ])->find($siswaId);

            if ($selectedSiswa) {
                $kelasId = $selectedSiswa->kelas_id;
                $tahunMulai = Pembayaran::tahunMulaiTarif($selectedSiswa->spp);
                $paidMonths = $selectedSiswa->pembayarans
                    ->where('spp_id', $selectedSiswa->spp_id)
                    ->whereNotNull('bulan_dibayar')
                    ->pluck('bulan_dibayar')
                    ->all();
                $dueMonths = $this->dueMonths($tahunMulai);

                foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
                    if (! in_array($periode['bulan'], $paidMonths, true)) {
                        $unpaidMonths[] = [
                            'bulan' => $periode['bulan'],
                            'tahun' => $periode['tahun'],
                            'nominal' => $selectedSiswa->spp?->nominal ?? 0,
                            'jatuh_tempo' => in_array($periode['bulan'], $dueMonths, true),
                        ];
                    }
                }

                $tahunAjaranAktif = TahunAjaran::query()->where('aktif', true)->value('nama');
                $tarifTahunAjaran = $selectedSiswa->spp?->tahun_ajaran;

                if ($tarifTahunAjaran && $tahunAjaranAktif && $tarifTahunAjaran !== $tahunAjaranAktif) {
                    $tunggakanTahunLalu = count($unpaidMonths) * ($selectedSiswa->spp?->nominal ?? 0);
                }

                // Retrieve all non-SPP tariffs applicable to this student's year or academic year
                $activeTahunAjaran = $selectedSiswa->spp?->tahun_ajaran;
                $nonSppTarifs = Spp::where('kategori', '!=', Spp::KATEGORI_SPP)
                    ->where(function ($q) use ($tahunMulai, $activeTahunAjaran) {
                        $q->whereIn('tahun', [$tahunMulai, $tahunMulai + 1])
                            ->orWhere('tahun_ajaran', $activeTahunAjaran);
                    })
                    ->orderBy('kategori')
                    ->get()
                    ->map(function (Spp $tarif) use ($selectedSiswa) {
                        $dibayar = (int) $selectedSiswa->pembayarans
                            ->where('spp_id', $tarif->id)
                            ->sum('jumlah_bayar');
                        $sisa = max(0, $tarif->nominal - $dibayar);
                        $lunas = $sisa === 0 && $dibayar > 0;

                        return [
                            'tarif' => $tarif,
                            'nominal' => $tarif->nominal,
                            'dibayar' => $dibayar,
                            'sisa' => $sisa,
                            'lunas' => $lunas,
                        ];
                    });
            }
        }

        $kelasList = Kelas::with('jurusan')
            ->withCount('siswas')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();

        $siswaKelas = collect();

        if ($kelasId) {
            $siswaKelas = Siswa::with([
                'spp',
                'pembayarans' => fn ($query) => $query
                    ->select(['id', 'siswa_id', 'spp_id', 'bulan_dibayar', 'tahun_dibayar', 'jumlah_bayar'])
                    ->whereNull('void_at'),
            ])
                ->where('kelas_id', $kelasId)
                ->when(! $sertakanArsip, fn ($query) => $query->where('is_archived', false))
                ->orderBy('nama')
                ->get()
                ->each(function (Siswa $siswa) {
                    $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);
                    $dibayar = $siswa->pembayarans
                        ->where('spp_id', $siswa->spp_id)
                        ->whereNotNull('bulan_dibayar')
                        ->pluck('bulan_dibayar')
                        ->all();
                    $siswa->jumlah_dibayar = count($dibayar);
                    $siswa->jumlah_tunggakan = count(array_diff($this->dueMonths($tahunMulai), $dibayar));
                });
        }

        return view('pembayaran.create', [
            'kelasList' => $kelasList,
            'kelasId' => $kelasId ? (int) $kelasId : null,
            'siswaKelas' => $siswaKelas,
            'selectedSiswa' => $selectedSiswa,
            'unpaidMonths' => $unpaidMonths,
            'nonSppTarifs' => $nonSppTarifs,
            'sertakanArsip' => $sertakanArsip,
            'tunggakanTahunLalu' => $tunggakanTahunLalu,
        ]);
    }

    /**
     * Months that are already due for a tariff year, based on today's date.
     * The school year runs July to June and is keyed by its starting year.
     *
     * @return array<int, string>
     */
    protected function dueMonths(int $tarifTahun): array
    {
        $now = now();
        $tahunAjaranMulai = $now->month >= 7 ? $now->year : $now->year - 1;

        if ($tarifTahun > $tahunAjaranMulai) {
            return [];
        }

        if ($tarifTahun < $tahunAjaranMulai) {
            return Pembayaran::TAHUN_AJARAN_BULAN_LIST;
        }

        $indexSekarang = ($now->month + 5) % 12;

        return array_slice(Pembayaran::TAHUN_AJARAN_BULAN_LIST, 0, $indexSekarang + 1);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        return Cache::lock(
            'pembayaran-siswa:'.((int) $request->input('siswa_id', 0)),
            10,
        )->block(5, fn (): RedirectResponse => $this->storeTerlindungi($request));
    }

    protected function storeTerlindungi(Request $request): RedirectResponse
    {
        $jenisTransaksi = $request->input('jenis_transaksi', 'spp');

        // Handle Non-SPP payment (Uang Gedung, Uang Seragam, Biaya Praktik, etc.)
        if ($jenisTransaksi === 'non_spp') {
            $validated = $request->validate([
                'siswa_id' => ['required', 'exists:siswas,id'],
                'spp_id' => ['required', 'exists:spps,id'],
                'jumlah_bayar' => ['required', 'integer', 'min:1000'],
                'tgl_bayar' => ['required', 'date'],
                'metode_pembayaran' => ['required', 'string', 'max:30'],
                'catatan' => ['nullable', 'string', 'max:255'],
            ], [
                'siswa_id.required' => 'Pilih siswa yang akan melakukan pembayaran.',
                'spp_id.required' => 'Pilih pos tagihan yang akan dibayar.',
                'jumlah_bayar.required' => 'Jumlah nominal pembayaran wajib diisi.',
                'jumlah_bayar.min' => 'Jumlah nominal pembayaran minimal Rp 1.000.',
                'tgl_bayar.required' => 'Tanggal pembayaran wajib diisi.',
                'metode_pembayaran.required' => 'Metode pembayaran wajib dipilih.',
            ]);

            $siswa = Siswa::findOrFail($validated['siswa_id']);
            $tarif = Spp::findOrFail($validated['spp_id']);

            if (app(TanggunganPemerintahService::class)->isPosDitanggung($siswa, $tarif)) {
                return back()->withInput()->with('error', "Tagihan {$tarif->kategori} ditanggung pemerintah.");
            }

            $sudahDibayar = (int) Pembayaran::where('siswa_id', $siswa->id)
                ->where('spp_id', $tarif->id)
                ->whereNull('void_at')
                ->sum('jumlah_bayar');

            $sisa = max(0, $tarif->nominal - $sudahDibayar);

            if ($sisa <= 0) {
                return back()->withInput()->with('error', "Tagihan {$tarif->kategori} untuk siswa ini sudah lunas.");
            }

            if ($validated['jumlah_bayar'] > $sisa) {
                return back()->withInput()->with('error', 'Jumlah bayar melebihi sisa tagihan (Maksimal Rp '.number_format($sisa, 0, ',', '.').').');
            }

            $pembayaran = Pembayaran::create([
                'kode_transaksi' => Pembayaran::generateKodeTransaksi(),
                'user_id' => auth()->id(),
                'siswa_id' => $siswa->id,
                'spp_id' => $tarif->id,
                'tgl_bayar' => $validated['tgl_bayar'],
                'bulan_dibayar' => null,
                'tahun_dibayar' => $tarif->tahun,
                'jumlah_bayar' => $validated['jumlah_bayar'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'catatan' => $validated['catatan'] ?: ('Pembayaran '.($tarif->nama ?: $tarif->kategori)." ({$tarif->tahun_ajaran}) an. {$siswa->nama}"),
            ]);

            LogAktivitas::catat(
                'pembayaran.dibuat',
                "Pembayaran {$tarif->kategori} ({$pembayaran->formattedJumlahBayar()}) an. {$siswa->nama} ({$pembayaran->kode_transaksi})",
                $pembayaran,
                $pembayaran->only(['kode_transaksi', 'siswa_id', 'spp_id', 'jumlah_bayar', 'metode_pembayaran', 'tgl_bayar'])
            );

            return redirect()->route('pembayaran.show', $pembayaran->id)
                ->with('success', "Transaksi pembayaran {$tarif->kategori} berhasil disimpan. Kuitansi siap dicetak.");
        }

        // SPP Monthly payment flow
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
        $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);
        $tanggunganPemerintah = app(TanggunganPemerintahService::class);

        $bulanDitanggung = collect($validated['bulan_dibayar'])
            ->filter(fn (string $bulan): bool => $tanggunganPemerintah->isSppDitanggung(
                $siswa,
                $siswa->spp,
                $bulan,
                Pembayaran::tahunUntukBulan($bulan, $tahunMulai),
            ))
            ->values();

        if ($bulanDitanggung->isNotEmpty()) {
            return back()->withInput()->with(
                'error',
                'Bulan SPP berikut ditanggung pemerintah: '.$bulanDitanggung->implode(', ').'.',
            );
        }

        DB::transaction(function () use ($validated, $siswa, $nominalPerBulan, $tahunMulai, &$createdPembayarans) {
            foreach ($validated['bulan_dibayar'] as $bulan) {
                $tahun = Pembayaran::tahunUntukBulan($bulan, $tahunMulai);

                $dibayar = (int) Pembayaran::where('siswa_id', $siswa->id)
                    ->where('spp_id', $siswa->spp_id)
                    ->where('bulan_dibayar', $bulan)
                    ->whereNull('void_at')
                    ->sum('jumlah_bayar');

                $sisa = max(0, $nominalPerBulan - $dibayar);

                if ($sisa <= 0) {
                    continue;
                }

                $pembayaran = Pembayaran::create([
                    'kode_transaksi' => Pembayaran::generateKodeTransaksi(),
                    'user_id' => auth()->id(),
                    'siswa_id' => $siswa->id,
                    'spp_id' => $siswa->spp_id,
                    'tgl_bayar' => $validated['tgl_bayar'],
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => $tahun,
                    'jumlah_bayar' => $sisa,
                    'metode_pembayaran' => $validated['metode_pembayaran'],
                    'catatan' => $validated['catatan'] ?? ('Pembayaran SPP Bulan '.$bulan.' '.$tahun),
                ]);

                LogAktivitas::catat(
                    'pembayaran.dibuat',
                    "Pembayaran SPP {$bulan} {$tahun} atas nama {$siswa->nama} ({$pembayaran->kode_transaksi})",
                    $pembayaran,
                    $pembayaran->only(['kode_transaksi', 'siswa_id', 'bulan_dibayar', 'tahun_dibayar', 'jumlah_bayar', 'metode_pembayaran', 'tgl_bayar'])
                );

                $createdPembayarans[] = $pembayaran;
            }
        });

        if (empty($createdPembayarans)) {
            return back()->withInput()->with('error', 'Bulan yang Anda pilih sudah pernah dibayar sebelumnya.');
        }

        $firstTrx = $createdPembayarans[0];

        return redirect()->route('pembayaran.show', $firstTrx->id)
            ->with('success', 'Transaksi berhasil disimpan ('.count($createdPembayarans).' bulan SPP). Kuitansi siap dicetak.');
    }

    /**
     * Redirect the legacy receipt detail page to the printable receipt.
     */
    public function show(Pembayaran $pembayaran): RedirectResponse
    {
        return redirect()->route('pembayaran.cetak', $pembayaran);
    }

    /**
     * Print receipt view for single or batch payment.
     */
    public function cetak(Pembayaran $pembayaran): View
    {
        $pembayaran->load(['siswa.kelas.jurusan', 'user', 'spp']);

        return view('pembayaran.cetak', [
            'pembayaran' => $pembayaran,
            'relatedPayments' => $pembayaran->relatedBatchPayments(),
        ]);
    }

    /**
     * Void a payment instead of deleting its financial record.
     */
    public function destroy(Request $request, Pembayaran $pembayaran): RedirectResponse
    {
        $alasan = trim((string) $request->input('alasan', '')) ?: 'Dibatalkan oleh administrator.';

        try {
            app(PembayaranService::class)->voidPembayaran($pembayaran, (int) auth()->id(), $alasan);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('pembayaran.index')->with('success', 'Transaksi pembayaran berhasil di-void. Riwayat dan nomor kuitansi tetap tersimpan.');
    }
}
