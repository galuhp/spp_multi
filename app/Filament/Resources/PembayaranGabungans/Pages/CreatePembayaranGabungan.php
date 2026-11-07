<?php

namespace App\Filament\Resources\PembayaranGabungans\Pages;

use App\Filament\Resources\PembayaranGabungans\PembayaranGabunganResource;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\Siswa;
use App\Models\Spp;
use App\Support\Pembayaran\PembayaranService;
use App\Support\PembayaranMassal\PembayaranMassalService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CreatePembayaranGabungan extends CreateRecord
{
    protected static string $resource = PembayaranGabunganResource::class;

    public string $mode = 'kompetensi';

    public ?int $jurusan_id = null;

    public ?string $tingkat = null;

    public ?int $kelas_id = null;

    /**
     * @var array<int, int|string>
     */
    public array $siswa_ids = [];

    /**
     * @var array<int, string>
     */
    public array $bulan = [];

    /**
     * @var array<int, int|string>
     */
    public array $non_spp_ids = [];

    public bool $modal = false;

    public bool $menyimpan = false;

    public int $totalEstimasi = 0;

    public ?string $galat = null;

    public function getTitle(): string
    {
        return 'Entry Pembayaran Kolektif';
    }

    #[Unexposed]
    public function getBreadcrumb(): string
    {
        return 'Entry Pembayaran Kolektif';
    }

    public function getView(): string
    {
        return 'filament.pages.pembayaran-massal-baru';
    }

    /**
     * @return Collection<int, Jurusan>
     */
    public function getJurusanOptionsProperty(): Collection
    {
        return Jurusan::query()->orderBy('nama')->get();
    }

    /**
     * @return array<string, string>
     */
    public function getTingkatOptionsProperty(): array
    {
        return \App\Models\ProfilSekolah::tingkatSppOptions();
    }

    /**
     * Classes available for the current mode and filter.
     *
     * @return Collection<int, Kelas>
     */
    public function getKelasTersediaProperty(): Collection
    {
        if ($this->mode === 'kompetensi' && ! $this->jurusan_id) {
            return collect();
        }

        if ($this->mode === 'tingkat' && ! $this->tingkat) {
            return collect();
        }

        return Kelas::query()
            ->with(['jurusan', 'tahunAjaran'])
            ->withCount(['siswas' => fn ($query) => $query->aktif()])
            ->when($this->mode === 'kompetensi', fn ($query) => $query->where('jurusan_id', $this->jurusan_id))
            ->when($this->mode === 'tingkat', fn ($query) => $query->whereIn(
                'tingkat',
                array_unique([$this->tingkat, $this->formatTingkat($this->tingkat)]),
            ))
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get();
    }

    protected ?Collection $cachedSiswa = null;

    protected ?int $cachedSiswaKelasId = null;

    /**
     * Active students of the selected class, ordered by name.
     *
     * @return Collection<int, Siswa>
     */
    public function getSiswaProperty(): Collection
    {
        if (! $this->kelas_id) {
            return collect();
        }

        if ($this->cachedSiswa !== null && $this->cachedSiswaKelasId === $this->kelas_id) {
            return $this->cachedSiswa;
        }

        $this->cachedSiswaKelasId = $this->kelas_id;

        return $this->cachedSiswa = Siswa::query()
            ->where('kelas_id', $this->kelas_id)
            ->aktif()
            ->orderBy('nama')
            ->get();
    }

    /**
     * Months that still have an open SPP bill for at least one selected student,
     * preserving the academic year order (Juli -> Juni).
     *
     * @return array<string, string>
     */
    public function getBulanOptionsProperty(): array
    {
        if (! $this->kelas_id || $this->siswa_ids === []) {
            return [];
        }

        $service = app(PembayaranService::class);
        $bulanTerbuka = [];

        foreach ($this->siswaTerpilih() as $siswa) {
            foreach ($service->tagihanTerbuka($siswa) as $item) {
                if (($item['tipe'] ?? null) === 'spp' && ! empty($item['bulan']) && (int) ($item['sisa'] ?? 0) > 0) {
                    $bulanTerbuka[(string) $item['bulan']] = true;
                }
            }

            if (count($bulanTerbuka) === count(Pembayaran::TAHUN_AJARAN_BULAN_LIST)) {
                break;
            }
        }

        $tersedia = array_values(array_filter(
            Pembayaran::TAHUN_AJARAN_BULAN_LIST,
            static fn (string $namaBulan): bool => isset($bulanTerbuka[$namaBulan]),
        ));

        if ($tersedia === []) {
            return [];
        }

        return array_combine($tersedia, $tersedia) ?: [];
    }

    /**
     * Open SPP months grouped by calendar year (e.g. 2026 => ['Juli', ...], 2027 => ['Januari', ...]).
     *
     * @return array<int, array<int, string>>
     */
    public function getBulanGroupedByTahunProperty(): array
    {
        $tersedia = array_values($this->bulanOptions);

        if ($tersedia === []) {
            return [];
        }

        $info = $this->infoTahunAjaran;
        $tahunMulai = $info['tahun_mulai'];
        $grouped = [];

        foreach ($tersedia as $namaBulan) {
            $tahun = Pembayaran::tahunUntukBulan($namaBulan, $tahunMulai);
            $grouped[$tahun][] = $namaBulan;
        }

        return $grouped;
    }

    /**
     * Academic year metadata for the currently selected class/students.
     *
     * @return array{tahun_ajaran: string, tahun_mulai: int, tahun_akhir: int}
     */
    public function getInfoTahunAjaranProperty(): array
    {
        $siswaSample = $this->siswaTerpilih()->first()
            ?? ($this->kelas_id
                ? Siswa::query()->with(['spp', 'kelas.tahunAjaran'])->where('kelas_id', $this->kelas_id)->aktif()->first()
                : null);

        $kelas = $siswaSample?->kelas
            ?? ($this->kelas_id ? Kelas::query()->with('tahunAjaran')->find($this->kelas_id) : null);

        $tarif = $siswaSample?->spp;
        $labelTahunAjaran = $tarif?->tahun_ajaran ?? $kelas?->tahunAjaran?->nama;

        if ($tarif) {
            $tahunMulai = Pembayaran::tahunMulaiTarif($tarif);
        } elseif ($labelTahunAjaran && preg_match('/^\s*(\d{4})\s*[\/-]\s*(\d{4})\s*$/', (string) $labelTahunAjaran, $m)) {
            $tahunMulai = (int) $m[1];
        } else {
            $tahunMulai = (int) now()->year;
        }

        $tahunAkhir = $tahunMulai + 1;

        if (! filled($labelTahunAjaran)) {
            $labelTahunAjaran = "{$tahunMulai}/{$tahunAkhir}";
        }

        return [
            'tahun_ajaran' => (string) $labelTahunAjaran,
            'tahun_mulai' => $tahunMulai,
            'tahun_akhir' => $tahunAkhir,
        ];
    }

    /**
     * Non-SPP tariffs applicable to the selected class.
     *
     * @return Collection<int, Spp>
     */
    public function getNonSppOptionsProperty(): Collection
    {
        if (! $this->kelas_id) {
            return collect();
        }

        $kelas = Kelas::query()->with('tahunAjaran')->find($this->kelas_id);

        if (! $kelas) {
            return collect();
        }

        $contohSiswa = Siswa::query()
            ->with('spp')
            ->where('kelas_id', $kelas->id)
            ->aktif()
            ->first();

        $tahunAjaran = $contohSiswa?->spp?->tahun_ajaran ?? $kelas->tahunAjaran?->nama;
        $tahunTarif = $contohSiswa?->spp?->tahun
            ?? ($tahunAjaran && preg_match('/(\d{4})/', (string) $tahunAjaran, $m) ? (int) $m[1] : null);
        $tingkat = Spp::normalisasiTingkat($kelas->tingkat);
        $variasiTingkat = array_values(array_unique(array_filter([
            $tingkat,
            $tingkat ? (Spp::TINGKAT_LIST[$tingkat] ?? null) : null,
        ])));

        return Spp::query()
            ->where('kategori', '!=', Spp::KATEGORI_SPP)
            ->when($tahunTarif || $tahunAjaran, function ($query) use ($tahunTarif, $tahunAjaran): void {
                $query->where(function ($subquery) use ($tahunTarif, $tahunAjaran): void {
                    if ($tahunTarif) {
                        $subquery->whereIn('tahun', [$tahunTarif, $tahunTarif + 1]);
                    }

                    if ($tahunAjaran) {
                        if ($tahunTarif) {
                            $subquery->orWhere('tahun_ajaran', $tahunAjaran);
                        } else {
                            $subquery->where('tahun_ajaran', $tahunAjaran);
                        }
                    }
                });
            })
            ->where(function ($query) use ($variasiTingkat): void {
                $query->whereNull('tingkat');

                if ($variasiTingkat !== []) {
                    $query->orWhereIn('tingkat', $variasiTingkat);
                }
            })
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();
    }

    public function updatedMode(): void
    {
        $this->jurusan_id = null;
        $this->tingkat = null;
        $this->resetPilihan();
    }

    public function updatedJurusanId(): void
    {
        $this->resetPilihan();
    }

    public function updatedTingkat(): void
    {
        $this->resetPilihan();
    }

    public function updatedKelasId(): void
    {
        $this->cachedSiswa = null;
        $this->cachedSiswaKelasId = null;

        $this->siswa_ids = $this->siswa
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->bulan = [];
        $this->non_spp_ids = [];
        $this->totalEstimasi = 0;
        $this->galat = null;
    }

    public function updatedSiswaIds(): void
    {
        if ($this->bulan !== []) {
            $this->bulan = array_values(array_intersect($this->bulan, array_values($this->bulanOptions)));
        }

        if ($this->modal) {
            $this->hitungTotal();
        }
    }

    public function updatedBulan(): void
    {
        $this->hitungTotal();
    }

    public function updatedNonSppIds(): void
    {
        $this->hitungTotal();
    }

    public function pilihSemua(): void
    {
        $this->siswa_ids = $this->siswa
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($this->modal) {
            $this->hitungTotal();
        }
    }

    public function kosongkan(): void
    {
        $this->siswa_ids = [];
        $this->bulan = [];
        $this->totalEstimasi = 0;
    }

    public function toggleSemua(): void
    {
        if (count($this->siswa_ids) === $this->siswa->count()) {
            $this->kosongkan();

            return;
        }

        $this->pilihSemua();
    }

    public function pilihSemuaBulan(): void
    {
        $this->bulan = array_values($this->bulanOptions);
        $this->hitungTotal();
    }

    public function kosongkanBulan(): void
    {
        $this->bulan = [];
        $this->hitungTotal();
    }

    public function pilihSemuaNonSpp(): void
    {
        $this->non_spp_ids = $this->nonSppOptions
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $this->hitungTotal();
    }

    public function kosongkanNonSpp(): void
    {
        $this->non_spp_ids = [];
        $this->hitungTotal();
    }

    public function bukaModal(): void
    {
        if ($this->siswa_ids === []) {
            $this->galat = 'Pilih minimal satu siswa.';

            return;
        }

        if ($this->bulan !== []) {
            $this->bulan = array_values(array_intersect($this->bulan, array_values($this->bulanOptions)));
        }

        $this->galat = null;
        $this->modal = true;
        $this->hitungTotal();
    }

    public function tutupModal(): void
    {
        $this->modal = false;
        $this->galat = null;
    }

    public function hitungTotal(): void
    {
        $bulan = array_values(array_unique($this->bulan));
        $nonSppIds = array_values(array_unique(array_map('intval', $this->non_spp_ids)));

        if (($bulan === [] && $nonSppIds === []) || $this->siswa_ids === []) {
            $this->totalEstimasi = 0;

            return;
        }

        $service = app(PembayaranService::class);
        $total = 0;

        foreach ($this->siswaTerpilih() as $siswa) {
            $terbuka = collect($service->tagihanTerbuka($siswa));
            $perBulan = $terbuka->where('tipe', 'spp')->keyBy('bulan');
            $perPos = $terbuka->where('tipe', 'non_spp')->keyBy('spp_id');

            foreach ($bulan as $namaBulan) {
                $item = $perBulan->get($namaBulan);

                if ($item) {
                    $total += (int) $item['sisa'];
                }
            }

            foreach ($nonSppIds as $sppId) {
                $item = $perPos->get($sppId);

                if ($item) {
                    $total += (int) $item['sisa'];
                }
            }
        }

        $this->totalEstimasi = $total;
    }

    public function simpan(): void
    {
        if ($this->menyimpan) {
            return;
        }

        $this->galat = null;
        $bulan = array_values(array_unique($this->bulan));
        $nonSppIds = array_values(array_unique(array_map('intval', $this->non_spp_ids)));
        $targetCount = count($bulan) + count($nonSppIds);

        if ($this->kelas_id === null) {
            $this->galat = 'Pilih kelas terlebih dahulu.';

            return;
        }

        if ($this->siswa_ids === []) {
            $this->galat = 'Pilih minimal satu siswa.';

            return;
        }

        if ($targetCount === 0) {
            $this->galat = 'Pilih minimal satu bulan SPP atau tarif biaya lainnya.';

            return;
        }

        $this->menyimpan = true;

        try {
            $siswaList = $this->siswaTerpilih();
            $service = app(PembayaranService::class);
            $alokasiPerSiswa = [];
            $total = 0;
            $dilewati = [];
            $sebagian = 0;

            foreach ($siswaList as $siswa) {
                $terbuka = collect($service->tagihanTerbuka($siswa));
                $perBulan = $terbuka->where('tipe', 'spp')->keyBy('bulan');
                $perPos = $terbuka->where('tipe', 'non_spp')->keyBy('spp_id');
                $alokasi = [];

                foreach ($bulan as $namaBulan) {
                    $item = $perBulan->get($namaBulan);

                    if (! $item) {
                        continue;
                    }

                    $alokasi[] = [
                        'tagihan' => $item['key'],
                        'nominal' => (int) $item['sisa'],
                    ];
                    $total += (int) $item['sisa'];
                }

                foreach ($nonSppIds as $sppId) {
                    $item = $perPos->get($sppId);

                    if (! $item) {
                        continue;
                    }

                    $alokasi[] = [
                        'tagihan' => $item['key'],
                        'nominal' => (int) $item['sisa'],
                    ];
                    $total += (int) $item['sisa'];
                }

                if ($alokasi === []) {
                    $dilewati[] = $siswa->nama;

                    continue;
                }

                if (count($alokasi) < $targetCount) {
                    $sebagian++;
                }

                $alokasiPerSiswa[$siswa->id] = $alokasi;
            }

            if ($alokasiPerSiswa === []) {
                $this->galat = 'Tidak ada tagihan terbuka untuk item yang dipilih pada siswa terpilih.';

                return;
            }

            DB::beginTransaction();

            $gabungan = PembayaranGabungan::query()->create([
                'kode' => $this->kodeBaru(),
                'tanggal' => now()->format('Y-m-d'),
                'sumber' => PembayaranGabungan::SUMBER_SETORAN_KELAS,
                'metode' => PembayaranGabungan::METODE_TUNAI,
                'kelas_id' => $this->kelas_id,
                'total_diterima' => $total,
                'status' => PembayaranGabungan::STATUS_DRAFT,
                'idempotency_token' => (string) Str::uuid(),
                'dibuat_oleh' => auth()->id(),
            ]);

            $massal = app(PembayaranMassalService::class);

            foreach ($siswaList as $siswa) {
                if (! isset($alokasiPerSiswa[$siswa->id])) {
                    continue;
                }

                $jumlah = (int) collect($alokasiPerSiswa[$siswa->id])->sum('nominal');

                $massal->tambahBaris(
                    $gabungan,
                    $siswa->id,
                    $jumlah,
                    null,
                    $alokasiPerSiswa[$siswa->id],
                );
            }

            $hasil = $massal->posting($gabungan, (int) auth()->id());

            if (! $hasil['berhasil']) {
                throw new \RuntimeException($hasil['pesan'] ?? 'Posting gagal.');
            }

            DB::commit();

            $pesan = $this->pesanDilewati($dilewati, $sebagian);

            if ($pesan) {
                session()->flash('massal_info', $pesan);
            }

            $this->redirect(route('pembayaran.gabungan.kuitansi', [
                'gabungan' => $gabungan,
                'auto' => 1,
            ]));
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->galat = $exception->getMessage();
        } finally {
            $this->menyimpan = false;
        }
    }

    /**
     * @return Collection<int, Siswa>
     */
    protected function siswaTerpilih(): Collection
    {
        return Siswa::query()
            ->with(['spp', 'kelas'])
            ->where('kelas_id', $this->kelas_id)
            ->whereIn('id', $this->siswa_ids)
            ->aktif()
            ->orderBy('nama')
            ->get();
    }

    protected function resetPilihan(): void
    {
        $this->kelas_id = null;
        $this->siswa_ids = [];
        $this->bulan = [];
        $this->non_spp_ids = [];
        $this->modal = false;
        $this->totalEstimasi = 0;
        $this->galat = null;
    }

    protected function formatTingkat(?string $tingkat): string
    {
        return Spp::labelTingkat($tingkat);
    }

    /**
     * @param  array<int, string>  $dilewati
     */
    protected function pesanDilewati(array $dilewati, int $sebagian): ?string
    {
        $pesan = [];

        if ($dilewati !== []) {
            $daftar = implode(', ', array_slice($dilewati, 0, 5));

            if (count($dilewati) > 5) {
                $daftar .= ', dan '.(count($dilewati) - 5).' lainnya';
            }

            $pesan[] = count($dilewati)." siswa tidak disertakan karena tidak memiliki tagihan terbuka pada bulan yang dipilih: {$daftar}.";
        }

        if ($sebagian > 0) {
            $pesan[] = "{$sebagian} siswa hanya dibayar untuk bulan yang masih terbuka; bulan yang sudah lunas dilewati.";
        }

        return $pesan === [] ? null : implode(' ', $pesan);
    }

    protected function kodeBaru(): string
    {
        do {
            $kode = 'PG-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (PembayaranGabungan::query()->where('kode', $kode)->exists());

        return $kode;
    }
}
