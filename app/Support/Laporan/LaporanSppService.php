<?php

namespace App\Support\Laporan;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LaporanSppService
{
    public const KELOMPOK_KELAS = 'kelas';

    public const KELOMPOK_JURUSAN = 'jurusan';

    public const KELOMPOK_TINGKAT = 'tingkat';

    /**
     * Cached monthly SPP tariffs keyed by academic year and level.
     *
     * @var array<string, Spp|null>
     */
    private array $tarifCache = [];

    /**
     * Resolve the selected academic year, falling back to the active one.
     */
    public function resolveTahunAjaran(int|string|null $tahunAjaranId): ?TahunAjaran
    {
        if (filled($tahunAjaranId)) {
            return TahunAjaran::query()->find($tahunAjaranId);
        }

        return $this->tahunAjaranAktif();
    }

    /**
     * Active academic year, or the newest one when no year is active.
     */
    public function tahunAjaranAktif(): ?TahunAjaran
    {
        return TahunAjaran::query()
            ->where('aktif', true)
            ->orderByDesc('nama')
            ->first()
            ?? TahunAjaran::query()->orderByDesc('nama')->first();
    }

    /**
     * Build the outstanding SPP report per student.
     *
     * @param  array{tahun_ajaran_id?: int|string|null, kelas_id?: int|string|null, tingkat?: string|null, status?: string|null, urutkan?: string|null}  $filters
     * @return array{
     *     tahun_ajaran: TahunAjaran|null,
     *     bulan: array<int, array{bulan: string, tahun: int}>,
     *     siswa: Collection<int, array<string, mixed>>,
     *     per_kelas: Collection<int, array<string, mixed>>,
     *     per_kategori_non_spp: Collection<int, array<string, mixed>>,
     *     ringkasan: array<string, int|float>
     * }
     */
    public function tunggakan(array $filters): array
    {
        $tahunAjaran = $this->resolveTahunAjaran($filters['tahun_ajaran_id'] ?? null);
        $bulan = $this->bulanJatuhTempo($tahunAjaran);
        $baris = $this->susunBaris($tahunAjaran, $filters, $bulan);

        $status = (string) ($filters['status'] ?? 'semua');

        if ($status !== '' && $status !== 'semua') {
            $baris = $baris->where('status', $status)->values();
        }

        $baris = $this->urutkanBaris($baris, (string) ($filters['urutkan'] ?? 'kelas'));

        return [
            'tahun_ajaran' => $tahunAjaran,
            'bulan' => $bulan,
            'siswa' => $baris,
            'per_kelas' => $this->ringkasPerKelas($baris),
            'per_kategori_non_spp' => $this->ringkasNonSppPerKategori($baris),
            'ringkasan' => $this->ringkas($baris),
        ];
    }

    /**
     * Build the received-vs-potential SPP report for a grouping dimension.
     *
     * @param  array{tahun_ajaran_id?: int|string|null, kelas_id?: int|string|null, tingkat?: string|null, jurusan_id?: int|string|null}  $filters
     * @return array{
     *     tahun_ajaran: TahunAjaran|null,
     *     bulan: array<int, array{bulan: string, tahun: int}>,
     *     kelompok: string,
     *     baris: Collection<int, array<string, mixed>>,
     *     per_kategori_non_spp: Collection<int, array<string, mixed>>,
     *     ringkasan: array<string, int|float>
     * }
     */
    public function rekapPotensi(array $filters, string $kelompok = self::KELOMPOK_KELAS): array
    {
        $kelompok = in_array($kelompok, [
            self::KELOMPOK_KELAS,
            self::KELOMPOK_JURUSAN,
            self::KELOMPOK_TINGKAT,
        ], true) ? $kelompok : self::KELOMPOK_KELAS;

        $tahunAjaran = $this->resolveTahunAjaran($filters['tahun_ajaran_id'] ?? null);
        $bulan = $this->bulanJatuhTempo($tahunAjaran);
        $baris = $this->susunBaris($tahunAjaran, $filters, $bulan);

        return [
            'tahun_ajaran' => $tahunAjaran,
            'bulan' => $bulan,
            'kelompok' => $kelompok,
            'baris' => $this->ringkasPerKelompok($baris, $kelompok),
            'per_kategori_non_spp' => $this->ringkasNonSppPerKategori($baris),
            'ringkasan' => $this->ringkas($baris),
        ];
    }

    /**
     * Build a student ledger for one academic year.
     *
     * SPP charges cover all twelve academic-year months. Non-SPP charges are
     * added when their tariff applies to the student's level. Valid payments
     * are inserted chronologically and a running balance is kept per row.
     *
     * @return array{
     *     siswa: Siswa,
     *     kelas: Kelas|null,
     *     tarif: Spp|null,
     *     tahun_ajaran: TahunAjaran,
     *     entries: Collection<int, array<string, mixed>>,
     *     void_payments: Collection<int, Pembayaran>,
     *     ringkasan: array<string, int|float>
     * }
     */
    public function kartuPembayaran(Siswa $siswa, TahunAjaran $tahunAjaran): array
    {
        $siswa->loadMissing(['kelas.jurusan', 'spp']);

        $kelas = $this->kelasSiswa($siswa, $tahunAjaran);
        $tarif = $kelas ? $this->tarifUntukSiswa($siswa, $kelas, $tahunAjaran) : null;
        $nominalSpp = (int) ($tarif?->nominal ?? 0);

        $bulanJatuhTempo = $this->bulanJatuhTempo($tahunAjaran);
        $kunciJatuhTempo = collect($bulanJatuhTempo)
            ->map(fn (array $periode): string => $periode['bulan'].'|'.$periode['tahun'])
            ->all();

        $entries = collect();
        $urutan = 0;

        if ($nominalSpp > 0) {
            foreach (Pembayaran::bulanTahunAjaran($this->tahunMulai($tahunAjaran)) as $periode) {
                $entries->push([
                    'tanggal' => Carbon::create($periode['tahun'], $this->nomorBulan($periode['bulan']), 1)->startOfDay(),
                    'tipe' => 'tagihan_spp',
                    'prioritas' => 0,
                    'urutan' => $urutan++,
                    'uraian' => 'Tagihan SPP '.$periode['bulan'].' '.$periode['tahun'],
                    'referensi' => null,
                    'debit' => $nominalSpp,
                    'kredit' => 0,
                    'status' => in_array($periode['bulan'].'|'.$periode['tahun'], $kunciJatuhTempo, true)
                        ? 'jatuh_tempo'
                        : 'belum_jatuh_tempo',
                    'meta' => ['bulan' => $periode['bulan'], 'tahun' => $periode['tahun']],
                ]);
            }
        }

        $tarifNonSpp = $this->tarifNonSppUntukKelas($kelas, $tahunAjaran);
        $tanggalNonSpp = $tahunAjaran->tanggal_mulai?->copy()->startOfDay()
            ?? Carbon::create($this->tahunMulai($tahunAjaran), 7, 1)->startOfDay();

        foreach ($tarifNonSpp as $tarifNon) {
            $nominalNonSpp = (int) $tarifNon->nominal;

            if ($nominalNonSpp <= 0) {
                continue;
            }

            $entries->push([
                'tanggal' => $tanggalNonSpp,
                'tipe' => 'tagihan_non_spp',
                'prioritas' => 0,
                'urutan' => $urutan++,
                'uraian' => 'Tagihan '.($tarifNon->nama ?: $tarifNon->kategori),
                'referensi' => null,
                'debit' => $nominalNonSpp,
                'kredit' => 0,
                'status' => 'jatuh_tempo',
                'meta' => ['tarif_id' => $tarifNon->id, 'kategori' => $tarifNon->kategori],
            ]);
        }

        $tarifIds = array_values(array_unique(array_filter(array_merge(
            $tarif?->id ? [$tarif->id] : [],
            $tarifNonSpp->pluck('id')->all(),
            Spp::query()
                ->where('kategori', Spp::KATEGORI_SPP)
                ->where('tahun_ajaran', $tahunAjaran->nama)
                ->pluck('id')
                ->all(),
        ))));

        $pembayarans = Pembayaran::query()
            ->with(['spp', 'user'])
            ->where('siswa_id', $siswa->id)
            ->whereIn('spp_id', $tarifIds)
            ->whereNull('void_at')
            ->orderBy('tgl_bayar')
            ->orderBy('id')
            ->get();

        foreach ($pembayarans as $pembayaran) {
            $entries->push([
                'tanggal' => $pembayaran->tgl_bayar->copy()->startOfDay(),
                'tipe' => 'pembayaran',
                'prioritas' => 1,
                'urutan' => $urutan++,
                'uraian' => 'Pembayaran '.$pembayaran->namaPos(),
                'referensi' => $pembayaran->kode_transaksi,
                'debit' => 0,
                'kredit' => (int) $pembayaran->jumlah_bayar,
                'status' => 'valid',
                'meta' => [
                    'metode' => $pembayaran->metode_pembayaran,
                    'petugas' => $pembayaran->user?->name,
                    'kategori' => $pembayaran->spp?->kategori,
                ],
            ]);
        }

        $entries = $entries
            ->sort(function (array $a, array $b): int {
                $tanggal = $a['tanggal'] <=> $b['tanggal'];

                if ($tanggal !== 0) {
                    return $tanggal;
                }

                return [$a['prioritas'], $a['urutan']] <=> [$b['prioritas'], $b['urutan']];
            })
            ->values();

        $saldo = 0;

        $entries = $entries->map(function (array $entry) use (&$saldo): array {
            $saldo += $entry['debit'] - $entry['kredit'];
            $entry['saldo'] = $saldo;

            return $entry;
        });

        $totalTagihan = (int) $entries->sum('debit');
        $totalDibayar = (int) $entries->sum('kredit');
        $tagihanJatuhTempo = (int) $entries
            ->filter(fn (array $entry): bool => $entry['tipe'] !== 'pembayaran' && $entry['status'] === 'jatuh_tempo')
            ->sum('debit');

        $voidPayments = Pembayaran::query()
            ->with(['spp', 'pemberiVoid'])
            ->where('siswa_id', $siswa->id)
            ->whereIn('spp_id', $tarifIds)
            ->whereNotNull('void_at')
            ->orderByDesc('void_at')
            ->get();

        return [
            'siswa' => $siswa,
            'kelas' => $kelas,
            'tarif' => $tarif,
            'tahun_ajaran' => $tahunAjaran,
            'entries' => $entries,
            'void_payments' => $voidPayments,
            'ringkasan' => [
                'total_tagihan' => $totalTagihan,
                'total_dibayar' => $totalDibayar,
                'total_tagihan_spp' => (int) $entries->where('tipe', 'tagihan_spp')->sum('debit'),
                'total_tagihan_non_spp' => (int) $entries->where('tipe', 'tagihan_non_spp')->sum('debit'),
                'tagihan_jatuh_tempo' => $tagihanJatuhTempo,
                'tagihan_jatuh_tempo_spp' => (int) $entries
                    ->filter(fn (array $entry): bool => $entry['tipe'] === 'tagihan_spp' && $entry['status'] === 'jatuh_tempo')
                    ->sum('debit'),
                'tagihan_jatuh_tempo_non_spp' => (int) $entries->where('tipe', 'tagihan_non_spp')->sum('debit'),
                'tunggakan_jatuh_tempo' => max(0, $tagihanJatuhTempo - $totalDibayar),
                'saldo' => $totalTagihan - $totalDibayar,
                'total_void' => (int) $voidPayments->sum('jumlah_bayar'),
                'jumlah_void' => $voidPayments->count(),
            ],
        ];
    }

    /**
     * Students mapped to their class for an academic year.
     *
     * @return Collection<int, array{siswa: Siswa, kelas: Kelas}>
     */
    public function siswaDenganKelas(?TahunAjaran $tahunAjaran): Collection
    {
        if (! $tahunAjaran) {
            return collect();
        }

        $kelasList = Kelas::query()
            ->with('jurusan')
            ->where(function ($query) use ($tahunAjaran): void {
                $query->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->orWhereNull('tahun_ajaran_id')
                    ->orWhereIn(
                        'id',
                        RiwayatKelas::query()
                            ->where('tahun_ajaran_id', $tahunAjaran->id)
                            ->select('kelas_id'),
                    );
            })
            ->orderBy('nama_kelas')
            ->get();

        if ($kelasList->isEmpty()) {
            return collect();
        }

        $kelasById = $kelasList->keyBy('id');
        $kelasPerSiswa = [];

        RiwayatKelas::query()
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->whereIn('kelas_id', $kelasById->keys())
            ->get(['siswa_id', 'kelas_id'])
            ->each(function (RiwayatKelas $riwayat) use (&$kelasPerSiswa): void {
                $kelasPerSiswa[(int) $riwayat->siswa_id] = (int) $riwayat->kelas_id;
            });

        $kelasSpesifikIds = $kelasList
            ->filter(fn (Kelas $kelas): bool => (int) $kelas->tahun_ajaran_id === (int) $tahunAjaran->id || ($kelas->tahun_ajaran_id === null && (bool) $tahunAjaran->aktif))
            ->pluck('id')
            ->all();

        if ($kelasSpesifikIds !== []) {
            Siswa::query()
                ->whereIn('kelas_id', $kelasSpesifikIds)
                ->get(['id', 'kelas_id'])
                ->each(function (Siswa $siswa) use (&$kelasPerSiswa): void {
                    $kelasPerSiswa[(int) $siswa->id] ??= (int) $siswa->kelas_id;
                });
        }

        if ($kelasPerSiswa === []) {
            return collect();
        }

        return Siswa::query()
            ->with(['kelas.jurusan', 'spp'])
            ->whereIn('id', array_keys($kelasPerSiswa))
            ->orderBy('nama')
            ->get()
            ->map(function (Siswa $siswa) use ($kelasPerSiswa, $kelasById): ?array {
                $kelas = $kelasById->get($kelasPerSiswa[(int) $siswa->id]);

                return $kelas ? ['siswa' => $siswa, 'kelas' => $kelas] : null;
            })
            ->filter()
            ->values();
    }

    /**
     * Resolve a student's class in an academic year, preferring class history.
     */
    public function kelasSiswa(Siswa $siswa, TahunAjaran $tahunAjaran): ?Kelas
    {
        $riwayat = RiwayatKelas::query()
            ->where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->first();

        if ($riwayat) {
            return Kelas::query()->with('jurusan')->find($riwayat->kelas_id);
        }

        $siswa->loadMissing('kelas.jurusan');

        if ($siswa->kelas && ((int) $siswa->kelas->tahun_ajaran_id === (int) $tahunAjaran->id || ($siswa->kelas->tahun_ajaran_id === null && (bool) $tahunAjaran->aktif))) {
            return $siswa->kelas;
        }

        return null;
    }

    /**
     * Non-SPP tariffs that apply to a class level in an academic year.
     *
     * @return Collection<int, Spp>
     */
    private function tarifNonSppUntukKelas(?Kelas $kelas, TahunAjaran $tahunAjaran): Collection
    {
        if (! $kelas || ! $this->tahunAjaranSudahDimulai($tahunAjaran)) {
            return collect();
        }

        $tahunMulai = $this->tahunMulai($tahunAjaran);
        $tingkat = Spp::normalisasiTingkat($kelas->tingkat);

        return Spp::query()
            ->where('kategori', '!=', Spp::KATEGORI_SPP)
            ->where(function ($query) use ($tahunAjaran, $tahunMulai): void {
                $query->where('tahun_ajaran', $tahunAjaran->nama)
                    ->orWhere('tahun', $tahunMulai);
            })
            ->get()
            ->filter(function (Spp $tarif) use ($tingkat): bool {
                if (blank($tarif->tingkat)) {
                    return true;
                }

                return Spp::normalisasiTingkat($tarif->tingkat) === $tingkat;
            })
            ->values();
    }

    /**
     * Convert an Indonesian month name to its calendar number.
     */
    private function nomorBulan(string $bulan): int
    {
        $indeks = array_search($bulan, Pembayaran::BULAN_LIST, true);

        return $indeks === false ? 1 : $indeks + 1;
    }

    /**
     * Months in an academic year that have already fallen due.
     *
     * A current academic year is counted through the current month. Past years
     * count all twelve months, while future years have no due months yet.
     *
     * @return array<int, array{bulan: string, tahun: int}>
     */
    public function bulanJatuhTempo(?TahunAjaran $tahunAjaran): array
    {
        if (! $tahunAjaran) {
            return [];
        }

        $tahunMulai = $this->tahunMulai($tahunAjaran);
        $semuaBulan = Pembayaran::bulanTahunAjaran($tahunMulai);
        $sekarang = Carbon::now();
        $tahunAjaranSekarang = $sekarang->month >= 7
            ? $sekarang->year
            : $sekarang->year - 1;

        if ($tahunMulai > $tahunAjaranSekarang) {
            return [];
        }

        if ($tahunMulai < $tahunAjaranSekarang) {
            return $semuaBulan;
        }

        $bulanSekarang = Pembayaran::BULAN_LIST[$sekarang->month - 1];
        $indeks = array_search($bulanSekarang, Pembayaran::TAHUN_AJARAN_BULAN_LIST, true);

        return array_slice($semuaBulan, 0, $indeks === false ? count($semuaBulan) : $indeks + 1);
    }

    /**
     * First calendar year of an academic year.
     */
    public function tahunMulai(TahunAjaran $tahunAjaran): int
    {
        if ($tahunAjaran->tanggal_mulai) {
            return (int) $tahunAjaran->tanggal_mulai->format('Y');
        }

        if (preg_match('/(\d{4})/', (string) $tahunAjaran->nama, $cocok)) {
            return (int) $cocok[1];
        }

        return (int) Carbon::now()->year;
    }

    /**
     * Build one row per student for the selected academic year.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, array{bulan: string, tahun: int}>  $bulan
     * @return Collection<int, array<string, mixed>>
     */
    private function susunBaris(?TahunAjaran $tahunAjaran, array $filters, array $bulan): Collection
    {
        if (! $tahunAjaran) {
            return collect();
        }

        $kelasList = Kelas::query()
            ->with('jurusan')
            ->where(function ($query) use ($tahunAjaran): void {
                $query->where('tahun_ajaran_id', $tahunAjaran->id)
                    ->orWhereNull('tahun_ajaran_id')
                    ->orWhereIn(
                        'id',
                        RiwayatKelas::query()
                            ->where('tahun_ajaran_id', $tahunAjaran->id)
                            ->select('kelas_id'),
                    );
            })
            ->when(filled($filters['kelas_id'] ?? null), fn ($query) => $query->whereKey($filters['kelas_id']))
            ->when(filled($filters['jurusan_id'] ?? null), fn ($query) => $query->where('jurusan_id', $filters['jurusan_id']))
            ->when(filled($filters['tingkat'] ?? null), function ($query) use ($filters): void {
                $query->whereIn('tingkat', $this->variasiTingkat((string) $filters['tingkat']));
            })
            ->orderBy('nama_kelas')
            ->get();

        if ($kelasList->isEmpty()) {
            return collect();
        }

        $kelasById = $kelasList->keyBy('id');
        $kelasPerSiswa = [];

        RiwayatKelas::query()
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->whereIn('kelas_id', $kelasById->keys())
            ->get(['siswa_id', 'kelas_id'])
            ->each(function (RiwayatKelas $riwayat) use (&$kelasPerSiswa): void {
                $kelasPerSiswa[(int) $riwayat->siswa_id] = (int) $riwayat->kelas_id;
            });

        $kelasSpesifikIds = $kelasList
            ->filter(fn (Kelas $kelas): bool => (int) $kelas->tahun_ajaran_id === (int) $tahunAjaran->id || ($kelas->tahun_ajaran_id === null && (bool) $tahunAjaran->aktif))
            ->pluck('id')
            ->all();

        if ($kelasSpesifikIds !== []) {
            Siswa::query()
                ->whereIn('kelas_id', $kelasSpesifikIds)
                ->get(['id', 'kelas_id'])
                ->each(function (Siswa $siswa) use (&$kelasPerSiswa): void {
                    $kelasPerSiswa[(int) $siswa->id] ??= (int) $siswa->kelas_id;
                });
        }

        if ($kelasPerSiswa === []) {
            return collect();
        }

        $siswaList = Siswa::query()
            ->with('spp')
            ->whereIn('id', array_keys($kelasPerSiswa))
            ->orderBy('nama')
            ->get();

        $tarifPerSiswa = [];

        foreach ($siswaList as $siswa) {
            $kelas = $kelasById->get($kelasPerSiswa[(int) $siswa->id]);

            if (! $kelas) {
                continue;
            }

            $tarifPerSiswa[(int) $siswa->id] = $this->tarifUntukSiswa($siswa, $kelas, $tahunAjaran);
        }

        $pembayaranPerBulan = $this->pembayaranPerBulan(
            $siswaList->pluck('id')->all(),
            $tahunAjaran,
        );

        $nonSppPerSiswa = $this->nonSppPerSiswa(
            $siswaList,
            $kelasPerSiswa,
            $kelasById,
            $tahunAjaran,
        );

        $siswaIds = $siswaList->pluck('id')->all();

        // Index all payments for multi-year timeline evaluation
        $semuaPembayaranSpp = Pembayaran::query()
            ->whereIn('siswa_id', $siswaIds)
            ->whereNull('void_at')
            ->whereNotNull('bulan_dibayar')
            ->get(['siswa_id', 'spp_id', 'bulan_dibayar', 'tahun_dibayar', 'jumlah_bayar']);

        $sppBayarMap = [];
        foreach ($semuaPembayaranSpp as $p) {
            $k1 = $p->siswa_id . '|' . $p->spp_id . '|' . $p->bulan_dibayar;
            $sppBayarMap[$k1] = ($sppBayarMap[$k1] ?? 0) + (int) $p->jumlah_bayar;
            $k2 = $p->siswa_id . '|' . $p->bulan_dibayar . '|' . $p->tahun_dibayar;
            $sppBayarMap[$k2] = ($sppBayarMap[$k2] ?? 0) + (int) $p->jumlah_bayar;
        }

        $semuaPembayaranNonSpp = Pembayaran::query()
            ->whereIn('siswa_id', $siswaIds)
            ->whereNull('void_at')
            ->whereNull('bulan_dibayar')
            ->get(['siswa_id', 'spp_id', 'jumlah_bayar']);

        $nonSppBayarMap = [];
        foreach ($semuaPembayaranNonSpp as $p) {
            $k = $p->siswa_id . '|' . $p->spp_id;
            $nonSppBayarMap[$k] = ($nonSppBayarMap[$k] ?? 0) + (int) $p->jumlah_bayar;
        }

        $tarifsPerTingkat = [];
        foreach (['10', '11', '12', '13', '7', '8', '9', '1', '2', '3', '4', '5', '6'] as $t) {
            $tarifsPerTingkat[$t] = [
                'spp' => Spp::untukTingkat($t),
                'non_spp' => Spp::nonSppUntukTingkat($t),
            ];
        }

        $tanggunganService = app(\App\Support\Pembayaran\TanggunganPemerintahService::class);

        return $siswaList
            ->map(function (Siswa $siswa) use (
                $kelasById,
                $kelasPerSiswa,
                $tarifPerSiswa,
                $pembayaranPerBulan,
                $nonSppPerSiswa,
                $bulan,
                $tarifsPerTingkat,
                $sppBayarMap,
                $nonSppBayarMap,
                $tanggunganService,
            ): array {
                /** @var Kelas $kelas */
                $kelas = $kelasById->get($kelasPerSiswa[(int) $siswa->id]);
                $tarif = $tarifPerSiswa[(int) $siswa->id] ?? null;
                $nominal = (int) ($tarif?->nominal ?? 0);
                $tingkat = Spp::normalisasiTingkat($kelas->tingkat);

                // 1. Current Academic Year (Running Timeline)
                $tagihanSppCur = 0;
                $dibayarSppCur = 0;
                $tunggakanSppCur = 0;
                $bulanTertunggak = [];

                foreach ($bulan as $periode) {
                    $tagihanSppCur += $nominal;

                    $kunci = $siswa->id.'|'.$periode['bulan'].'|'.$periode['tahun'];
                    $jumlahBayar = (int) ($pembayaranPerBulan[$kunci] ?? 0);

                    $dibayarSppCur += min($jumlahBayar, $nominal);

                    $sisa = max(0, $nominal - $jumlahBayar);
                    $tunggakanSppCur += $sisa;

                    if ($sisa > 0) {
                        $bulanTertunggak[] = $periode['bulan'].' '.$periode['tahun'];
                    }
                }

                $posNonSpp = $nonSppPerSiswa[(int) $siswa->id] ?? [];
                $tagihanNonSppCur = (int) collect($posNonSpp)->sum('nominal');
                $dibayarNonSppCur = (int) collect($posNonSpp)->sum('dibayar');
                $tunggakanNonSppCur = (int) collect($posNonSpp)->sum('tunggakan');
                $posNonSppTertunggak = collect($posNonSpp)
                    ->filter(fn (array $pos): bool => $pos['tunggakan'] > 0)
                    ->map(fn (array $pos): string => $pos['tarif']->nama ?: $pos['tarif']->kategori)
                    ->values()
                    ->all();

                // 2. Prior Grade Levels (Completed Past Timeline)
                $priorLevels = $this->tingkatSebelumnya((string) $tingkat);
                $tagihanSppPrior = 0;
                $dibayarSppPrior = 0;
                $tunggakanSppPrior = 0;
                $bulanTertunggakPrior = [];

                $tagihanNonSppPrior = 0;
                $dibayarNonSppPrior = 0;
                $tunggakanNonSppPrior = 0;
                $posNonSppTertunggakPrior = [];

                foreach ($priorLevels as $pLvl) {
                    $lvlTarifs = $tarifsPerTingkat[$pLvl] ?? [
                        'spp' => Spp::untukTingkat($pLvl),
                        'non_spp' => Spp::nonSppUntukTingkat($pLvl),
                    ];
                    $sppPrior = $lvlTarifs['spp'];

                    $selisih = max(1, (int) $tingkat - (int) $pLvl);
                    $taCurYear = $tahunAjaran ? (int) substr($tahunAjaran->nama, 0, 4) : 2026;
                    $taPriorYear = $taCurYear - $selisih;

                    if ($sppPrior && $sppPrior->isBulanan()) {
                        $nomPrior = (int) $sppPrior->nominal;
                        foreach (Pembayaran::TAHUN_AJARAN_BULAN_LIST as $bln) {
                            $thnPrior = in_array($bln, ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true) ? $taPriorYear : ($taPriorYear + 1);

                            if ($tanggunganService->isSppDitanggung($siswa, $sppPrior, $bln, $thnPrior)) {
                                continue;
                            }
                            $tagihanSppPrior += $nomPrior;
                            $bayar = (int) ($sppBayarMap[$siswa->id.'|'.$bln.'|'.$thnPrior] ?? 0);
                            $dibayarSppPrior += min($bayar, $nomPrior);
                            $sisa = max(0, $nomPrior - $bayar);
                            $tunggakanSppPrior += $sisa;
                            if ($sisa > 0) {
                                $bulanTertunggakPrior[] = "{$bln} (Kelas ".Spp::labelTingkat($pLvl).")";
                            }
                        }
                    }

                    foreach ($lvlTarifs['non_spp'] as $nonSppPrior) {
                        if ($tanggunganService->isPosDitanggung($siswa, $nonSppPrior)) {
                            continue;
                        }
                        $nomNon = (int) $nonSppPrior->nominal;
                        $tagihanNonSppPrior += $nomNon;
                        $bayar = (int) ($nonSppBayarMap[$siswa->id.'|'.$nonSppPrior->id] ?? 0);
                        $dibayarNonSppPrior += min($bayar, $nomNon);
                        $sisa = max(0, $nomNon - $bayar);
                        $tunggakanNonSppPrior += $sisa;
                        if ($sisa > 0) {
                            $posNonSppTertunggakPrior[] = ($nonSppPrior->nama ?: $nonSppPrior->kategori)." (Kelas ".Spp::labelTingkat($pLvl).")";
                        }
                        $posNonSpp[] = [
                            'tarif' => $nonSppPrior,
                            'nominal' => $nomNon,
                            'dibayar' => min($bayar, $nomNon),
                            'tunggakan' => $sisa,
                            'status' => $sisa === 0 ? 'lunas' : ($bayar > 0 ? 'sebagian' : 'belum'),
                        ];
                    }
                }

                // 3. Multi-year Timeline Totals
                $tagihanSpp = $tagihanSppCur + $tagihanSppPrior;
                $dibayarSpp = $dibayarSppCur + $dibayarSppPrior;
                $tunggakanSpp = $tunggakanSppCur + $tunggakanSppPrior;

                $tagihanNonSpp = $tagihanNonSppCur + $tagihanNonSppPrior;
                $dibayarNonSpp = $dibayarNonSppCur + $dibayarNonSppPrior;
                $tunggakanNonSpp = $tunggakanNonSppCur + $tunggakanNonSppPrior;

                $tagihan = $tagihanSpp + $tagihanNonSpp;
                $dibayar = $dibayarSpp + $dibayarNonSpp;
                $tunggakan = $tunggakanSpp + $tunggakanNonSpp;

                $semuaBulanTertunggak = array_merge($bulanTertunggakPrior, $bulanTertunggak);
                $semuaPosNonSppTertunggak = array_merge($posNonSppTertunggakPrior, $posNonSppTertunggak);

                $status = 'belum_jatuh_tempo';

                if ($bulan !== [] || $posNonSpp !== [] || $priorLevels !== []) {
                    if ($tagihan <= 0) {
                        $status = 'tanpa_tarif';
                    } elseif ($tunggakan > 0) {
                        $status = 'menunggak';
                    } else {
                        $status = 'lunas';
                    }
                }

                return [
                    'siswa' => $siswa,
                    'kelas' => $kelas,
                    'tarif' => $tarif,
                    'nominal' => $nominal,
                    'tagihan' => $tagihan,
                    'dibayar' => $dibayar,
                    'tunggakan' => $tunggakan,
                    'tagihan_spp' => $tagihanSpp,
                    'dibayar_spp' => $dibayarSpp,
                    'tunggakan_spp' => $tunggakanSpp,
                    'tagihan_non_spp' => $tagihanNonSpp,
                    'dibayar_non_spp' => $dibayarNonSpp,
                    'tunggakan_non_spp' => $tunggakanNonSpp,
                    'tunggakan_prior' => $tunggakanSppPrior + $tunggakanNonSppPrior,
                    'tunggakan_prior_spp' => $tunggakanSppPrior,
                    'tunggakan_prior_non_spp' => $tunggakanNonSppPrior,
                    'tunggakan_berjalan' => $tunggakanSppCur + $tunggakanNonSppCur,
                    'tunggakan_spp_berjalan' => $tunggakanSppCur,
                    'tunggakan_non_spp_berjalan' => $tunggakanNonSppCur,
                    'non_spp' => $posNonSpp,
                    'jumlah_pos_non_spp' => count($posNonSpp),
                    'jumlah_pos_non_spp_tertunggak' => count($semuaPosNonSppTertunggak),
                    'pos_non_spp_tertunggak' => $semuaPosNonSppTertunggak,
                    'jumlah_bulan_tertunggak' => count($semuaBulanTertunggak),
                    'bulan_tertunggak' => $semuaBulanTertunggak,
                    'status' => $status,
                ];
            })
            ->values();
    }

    /**
     * Build the applicable non-SPP charges and their payment progress per student.
     *
     * @param  Collection<int, Siswa>  $siswaList
     * @param  array<int, int>  $kelasPerSiswa
     * @param  Collection<int, Kelas>  $kelasById
     * @return array<int, array<int, array{tarif: Spp, nominal: int, dibayar: int, tunggakan: int, status: string}>>
     */
    private function nonSppPerSiswa(
        Collection $siswaList,
        array $kelasPerSiswa,
        Collection $kelasById,
        TahunAjaran $tahunAjaran,
    ): array {
        if (! $this->tahunAjaranSudahDimulai($tahunAjaran)) {
            return [];
        }

        $tahunMulai = $this->tahunMulai($tahunAjaran);

        $tarifList = Spp::query()
            ->where('kategori', '!=', Spp::KATEGORI_SPP)
            ->where(function ($query) use ($tahunAjaran, $tahunMulai): void {
                $query->where('tahun_ajaran', $tahunAjaran->nama)
                    ->orWhere('tahun', $tahunMulai);
            })
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        if ($tarifList->isEmpty()) {
            return [];
        }

        $pembayarans = Pembayaran::query()
            ->whereIn('siswa_id', $siswaList->pluck('id')->all())
            ->whereIn('spp_id', $tarifList->pluck('id')->all())
            ->whereNull('void_at')
            ->get(['siswa_id', 'spp_id', 'jumlah_bayar']);

        $dibayarPerSiswaTarif = [];

        foreach ($pembayarans as $pembayaran) {
            $kunci = $pembayaran->siswa_id.'|'.$pembayaran->spp_id;
            $dibayarPerSiswaTarif[$kunci] = ($dibayarPerSiswaTarif[$kunci] ?? 0) + (int) $pembayaran->jumlah_bayar;
        }

        $hasil = [];

        foreach ($siswaList as $siswa) {
            /** @var Kelas|null $kelas */
            $kelas = $kelasById->get($kelasPerSiswa[(int) $siswa->id]);

            if (! $kelas) {
                continue;
            }

            $tingkat = Spp::normalisasiTingkat($kelas->tingkat);
            $pos = [];

            foreach ($tarifList as $tarif) {
                $tingkatTarif = Spp::normalisasiTingkat($tarif->tingkat);

                if (filled($tarif->tingkat) && $tingkatTarif !== $tingkat) {
                    continue;
                }

                $nominal = (int) $tarif->nominal;
                $dibayar = (int) ($dibayarPerSiswaTarif[$siswa->id.'|'.$tarif->id] ?? 0);
                $sisa = max(0, $nominal - $dibayar);

                $pos[] = [
                    'tarif' => $tarif,
                    'nominal' => $nominal,
                    'dibayar' => min($dibayar, $nominal),
                    'tunggakan' => $sisa,
                    'status' => match (true) {
                        $nominal <= 0, $sisa === 0 => 'lunas',
                        $dibayar > 0 => 'sebagian',
                        default => 'belum',
                    },
                ];
            }

            if ($pos !== []) {
                $hasil[(int) $siswa->id] = $pos;
            }
        }

        return $hasil;
    }

    /**
     * Whether the selected academic year has already started.
     */
    private function tahunAjaranSudahDimulai(TahunAjaran $tahunAjaran): bool
    {
        $mulai = $this->tahunMulai($tahunAjaran);
        $sekarang = Carbon::now();
        $tahunAjaranSekarang = $sekarang->month >= 7
            ? $sekarang->year
            : $sekarang->year - 1;

        return $mulai <= $tahunAjaranSekarang;
    }

    /**
     * Sum non-voided payments per student, month, and year for the academic year.
     *
     * @param  array<int, int>  $siswaIds
     * @return array<string, int>
     */
    private function pembayaranPerBulan(array $siswaIds, TahunAjaran $tahunAjaran): array
    {
        if ($siswaIds === []) {
            return [];
        }

        $tarifIds = Spp::query()
            ->where('kategori', Spp::KATEGORI_SPP)
            ->where('tahun_ajaran', $tahunAjaran->nama)
            ->pluck('id')
            ->all();

        if ($tarifIds === []) {
            return [];
        }

        $tahunMulai = $this->tahunMulai($tahunAjaran);

        $pembayarans = Pembayaran::query()
            ->whereIn('siswa_id', $siswaIds)
            ->whereIn('spp_id', $tarifIds)
            ->whereIn('tahun_dibayar', [$tahunMulai, $tahunMulai + 1])
            ->whereNull('void_at')
            ->get(['siswa_id', 'bulan_dibayar', 'tahun_dibayar', 'jumlah_bayar']);

        $hasil = [];

        foreach ($pembayarans as $pembayaran) {
            if (blank($pembayaran->bulan_dibayar)) {
                continue;
            }

            $kunci = $pembayaran->siswa_id.'|'.$pembayaran->bulan_dibayar.'|'.$pembayaran->tahun_dibayar;
            $hasil[$kunci] = ($hasil[$kunci] ?? 0) + (int) $pembayaran->jumlah_bayar;
        }

        return $hasil;
    }

    /**
     * Find the monthly SPP tariff for a student's class and academic year.
     */
    private function tarifUntukSiswa(Siswa $siswa, Kelas $kelas, TahunAjaran $tahunAjaran): ?Spp
    {
        $tarif = $this->tarifUntukTingkat($kelas, $tahunAjaran);

        if ($tarif) {
            return $tarif;
        }

        if ($siswa->spp?->kategori === Spp::KATEGORI_SPP
            && $siswa->spp?->tahun_ajaran === $tahunAjaran->nama) {
            return $siswa->spp;
        }

        $riwayatSpp = RiwayatKelas::query()
            ->with('spp')
            ->where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $tahunAjaran->id)
            ->first()
            ?->spp;

        if ($riwayatSpp && $riwayatSpp->kategori === Spp::KATEGORI_SPP) {
            return $riwayatSpp;
        }

        return null;
    }

    /**
     * Resolve a level's monthly SPP tariff once per academic year.
     */
    private function tarifUntukTingkat(Kelas $kelas, TahunAjaran $tahunAjaran): ?Spp
    {
        $kunci = $tahunAjaran->nama.'|'.Spp::normalisasiTingkat($kelas->tingkat);

        if (array_key_exists($kunci, $this->tarifCache)) {
            return $this->tarifCache[$kunci];
        }

        $tarif = Spp::untukTingkat($kelas->tingkat, $tahunAjaran->nama)
            ?? Spp::query()
                ->where('kategori', Spp::KATEGORI_SPP)
                ->where('tahun_ajaran', $tahunAjaran->nama)
                ->whereIn('tingkat', $this->variasiTingkat($kelas->tingkat))
                ->orderByDesc('tahun')
                ->first();

        return $this->tarifCache[$kunci] = $tarif;
    }

    /**
     * Both numeric and roman forms of a level, for legacy data.
     *
     * @return array<int, string>
     */
    private function variasiTingkat(?string $tingkat): array
    {
        $normal = Spp::normalisasiTingkat($tingkat);

        if ($normal === null) {
            return [];
        }

        return array_values(array_unique(array_filter([
            $normal,
            Spp::TINGKAT_LIST[$normal] ?? null,
            $tingkat,
        ])));
    }

    /**
     * Prior completed grade levels for a given grade level along the student's study timeline.
     *
     * @return array<int, string>
     */
    public function tingkatSebelumnya(?string $tingkat): array
    {
        $normal = Spp::normalisasiTingkat($tingkat);

        if (! is_numeric($normal)) {
            return [];
        }

        $num = (int) $normal;

        if ($num >= 10 && $num <= 13) {
            return array_map('strval', range(10, $num - 1));
        }

        if ($num >= 7 && $num <= 9) {
            return array_map('strval', range(7, $num - 1));
        }

        if ($num >= 1 && $num <= 6) {
            return array_map('strval', range(1, $num - 1));
        }

        return [];
    }

    /**
     * Sort report rows by class/name or by the largest arrears.
     *
     * @param  Collection<int, array<string, mixed>>  $baris
     * @return Collection<int, array<string, mixed>>
     */
    private function urutkanBaris(Collection $baris, string $urutkan): Collection
    {
        if ($urutkan === 'tunggakan') {
            return $baris->sort(function (array $a, array $b): int {
                return [$b['tunggakan'], $a['siswa']->nama] <=> [$a['tunggakan'], $b['siswa']->nama];
            })->values();
        }

        return $baris->sort(function (array $a, array $b): int {
            return [$a['kelas']->nama_kelas, $a['siswa']->nama] <=> [$b['kelas']->nama_kelas, $b['siswa']->nama];
        })->values();
    }

    /**
     * Aggregate one student list into class-level totals.
     *
     * @param  Collection<int, array<string, mixed>>  $baris
     * @return Collection<int, array<string, mixed>>
     */
    private function ringkasPerKelas(Collection $baris): Collection
    {
        return $baris
            ->groupBy(fn (array $row): int => (int) $row['kelas']->id)
            ->map(function (Collection $group): array {
                /** @var Kelas $kelas */
                $kelas = $group->first()['kelas'];

                return ['kelas' => $kelas] + $this->ringkas($group);
            })
            ->sortBy(fn (array $row): string => $row['kelas']->nama_kelas)
            ->values();
    }

    /**
     * Aggregate student rows into the selected grouping dimension.
     *
     * @param  Collection<int, array<string, mixed>>  $baris
     * @return Collection<int, array<string, mixed>>
     */
    private function ringkasPerKelompok(Collection $baris, string $kelompok): Collection
    {
        return $baris
            ->groupBy(function (array $row) use ($kelompok): string {
                return match ($kelompok) {
                    self::KELOMPOK_JURUSAN => (string) ($row['kelas']->jurusan?->id ?? 0),
                    self::KELOMPOK_TINGKAT => (string) (Spp::normalisasiTingkat($row['kelas']->tingkat) ?? '-'),
                    default => (string) $row['kelas']->id,
                };
            })
            ->map(function (Collection $group, string $kunci) use ($kelompok): array {
                /** @var array<string, mixed> $contoh */
                $contoh = $group->first();
                /** @var Kelas $kelas */
                $kelas = $contoh['kelas'];

                $label = match ($kelompok) {
                    self::KELOMPOK_JURUSAN => trim(($kelas->jurusan?->kode ? $kelas->jurusan->kode.' — ' : '').($kelas->jurusan?->nama ?? 'Tanpa Jurusan')),
                    self::KELOMPOK_TINGKAT => 'Tingkat '.Spp::labelTingkat($kelas->tingkat),
                    default => $kelas->nama_kelas,
                };

                return [
                    'kunci' => $kunci,
                    'label' => $label,
                    'kelas' => $kelas,
                ] + $this->ringkas($group);
            })
            ->sortBy('label')
            ->values();
    }

    /**
     * Totals for a set of student rows.
     *
     * @param  Collection<int, array<string, mixed>>  $baris
     * @return array<string, int|float>
     */
    private function ringkas(Collection $baris): array
    {
        $tagihan = (int) $baris->sum(fn (array $row): int => $row['tagihan']);
        $dibayar = (int) $baris->sum(fn (array $row): int => $row['dibayar']);
        $tunggakan = (int) $baris->sum(fn (array $row): int => $row['tunggakan']);

        return [
            'jumlah_siswa' => $baris->count(),
            'jumlah_menunggak' => $baris->where('status', 'menunggak')->count(),
            'jumlah_lunas' => $baris->where('status', 'lunas')->count(),
            'jumlah_tanpa_tarif' => $baris->where('status', 'tanpa_tarif')->count(),
            'jumlah_siswa_non_spp' => $baris->filter(fn (array $row): bool => ($row['jumlah_pos_non_spp'] ?? 0) > 0)->count(),
            'tagihan' => $tagihan,
            'dibayar' => $dibayar,
            'tunggakan' => $tunggakan,
            'tagihan_spp' => (int) $baris->sum(fn (array $row): int => $row['tagihan_spp']),
            'dibayar_spp' => (int) $baris->sum(fn (array $row): int => $row['dibayar_spp']),
            'tunggakan_spp' => (int) $baris->sum(fn (array $row): int => $row['tunggakan_spp']),
            'tagihan_non_spp' => (int) $baris->sum(fn (array $row): int => $row['tagihan_non_spp']),
            'dibayar_non_spp' => (int) $baris->sum(fn (array $row): int => $row['dibayar_non_spp']),
            'tunggakan_non_spp' => (int) $baris->sum(fn (array $row): int => $row['tunggakan_non_spp']),
            'persentase' => $tagihan > 0 ? round(($dibayar / $tagihan) * 100, 1) : 0.0,
        ];
    }

    /**
     * Aggregate non-SPP charges per category for the printed breakdown.
     *
     * @param  Collection<int, array<string, mixed>>  $baris
     * @return Collection<int, array<string, mixed>>
     */
    private function ringkasNonSppPerKategori(Collection $baris): Collection
    {
        return $baris
            ->flatMap(fn (array $row): Collection => collect($row['non_spp'] ?? [])
                ->map(fn (array $pos): array => [
                    'siswa_id' => $row['siswa']->id,
                    'kategori' => $pos['tarif']->kategori,
                    'nominal' => $pos['nominal'],
                    'dibayar' => $pos['dibayar'],
                    'tunggakan' => $pos['tunggakan'],
                ]))
            ->groupBy('kategori')
            ->map(function (Collection $group, string $kategori): array {
                $potensi = (int) $group->sum('nominal');
                $realisasi = (int) $group->sum('dibayar');
                $tunggakan = (int) $group->sum('tunggakan');

                return [
                    'kategori' => $kategori,
                    'jumlah_siswa' => $group->pluck('siswa_id')->unique()->count(),
                    'jumlah_pos' => $group->count(),
                    'potensi' => $potensi,
                    'realisasi' => $realisasi,
                    'tunggakan' => $tunggakan,
                    'persentase' => $potensi > 0 ? round(($realisasi / $potensi) * 100, 1) : 0.0,
                ];
            })
            ->sortBy('kategori')
            ->values();
    }
}
