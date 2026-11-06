<?php

namespace App\Filament\Resources\PembayaranGabungans\Pages;

use App\Filament\Resources\PembayaranGabungans\PembayaranGabunganResource;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\Siswa;
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

    public bool $modal = false;

    public bool $menyimpan = false;

    public int $totalEstimasi = 0;

    public ?string $galat = null;

    public function getTitle(): string
    {
        return 'Entry Pembayaran Gabungan';
    }

    #[Unexposed]
    public function getBreadcrumb(): string
    {
        return 'Entry Pembayaran Gabungan';
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
     * @return array<int, string>
     */
    public function getTingkatOptionsProperty(): array
    {
        return [
            '10' => 'Kelas X',
            '11' => 'Kelas XI',
            '12' => 'Kelas XII',
        ];
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

        return Siswa::query()
            ->where('kelas_id', $this->kelas_id)
            ->aktif()
            ->orderBy('nama')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function getBulanOptionsProperty(): array
    {
        return array_combine(
            Pembayaran::TAHUN_AJARAN_BULAN_LIST,
            Pembayaran::TAHUN_AJARAN_BULAN_LIST,
        ) ?: [];
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
        $this->siswa_ids = $this->siswa
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->bulan = [];
        $this->totalEstimasi = 0;
        $this->galat = null;
    }

    public function updatedSiswaIds(): void
    {
        $this->hitungTotal();
    }

    public function updatedBulan(): void
    {
        $this->hitungTotal();
    }

    public function pilihSemua(): void
    {
        $this->siswa_ids = $this->siswa
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $this->hitungTotal();
    }

    public function kosongkan(): void
    {
        $this->siswa_ids = [];
        $this->hitungTotal();
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
        $this->bulan = Pembayaran::TAHUN_AJARAN_BULAN_LIST;
        $this->hitungTotal();
    }

    public function kosongkanBulan(): void
    {
        $this->bulan = [];
        $this->totalEstimasi = 0;
    }

    public function bukaModal(): void
    {
        if ($this->siswa_ids === []) {
            $this->galat = 'Pilih minimal satu siswa.';

            return;
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
        if ($this->bulan === [] || $this->siswa_ids === []) {
            $this->totalEstimasi = 0;

            return;
        }

        $service = app(PembayaranService::class);
        $total = 0;

        foreach ($this->siswaTerpilih() as $siswa) {
            $items = collect($service->tagihanTerbuka($siswa))->keyBy('bulan');

            foreach (array_unique($this->bulan) as $bulan) {
                $item = $items->get($bulan);

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

        if ($this->kelas_id === null) {
            $this->galat = 'Pilih kelas terlebih dahulu.';

            return;
        }

        if ($this->siswa_ids === []) {
            $this->galat = 'Pilih minimal satu siswa.';

            return;
        }

        if ($bulan === []) {
            $this->galat = 'Pilih minimal satu bulan pembayaran.';

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
                $items = collect($service->tagihanTerbuka($siswa))->keyBy('bulan');
                $alokasi = [];

                foreach ($bulan as $namaBulan) {
                    $item = $items->get($namaBulan);

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

                if (count($alokasi) < count($bulan)) {
                    $sebagian++;
                }

                $alokasiPerSiswa[$siswa->id] = $alokasi;
            }

            if ($alokasiPerSiswa === []) {
                $this->galat = 'Tidak ada tagihan terbuka untuk bulan yang dipilih pada siswa terpilih.';

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
        $this->modal = false;
        $this->totalEstimasi = 0;
        $this->galat = null;
    }

    protected function formatTingkat(?string $tingkat): string
    {
        return match ($tingkat) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            default => (string) $tingkat,
        };
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
