<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\Spp;
use App\Support\Pembayaran\TanggunganPemerintahService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class StatusTagihanPerSiswa2 extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static UnitEnum|string|null $navigationGroup = 'Portal Siswa';

    protected static ?string $navigationLabel = 'Status Tagihan Per Siswa 2';

    protected static ?string $title = 'Status Tagihan Per Siswa 2';

    protected static ?string $slug = 'status-tagihan-per-siswa-2';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.status-tagihan-per-siswa2';

    public ?int $selectedSiswaId = null;

    public string $cara_pilih = 'cari';

    public ?int $kelas_id = null;

    public string $cari = '';

    public string $status_filter = 'semua';

    public string $cari_pos = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user?->isSiswa()) {
            return true;
        }

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::PORTAL_SISWA);
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user?->isSiswa()) {
            $siswa = $user->siswa ?? Siswa::where('nisn', $user->username)->first();
            $this->selectedSiswaId = $siswa?->id;
        } else {
            $requestedSiswaId = request()->query('siswa_id');
            if ($requestedSiswaId && Siswa::query()->tanpaArsip()->where('id', $requestedSiswaId)->exists()) {
                $this->selectedSiswaId = (int) $requestedSiswaId;
            } else {
                $this->selectedSiswaId = Siswa::query()->tanpaArsip()->orderBy('nama')->value('id');
            }
        }
    }

    public function updatedCaraPilih(): void
    {
        $this->kelas_id = null;
        $this->cari = '';
        $this->selectedSiswaId = null;
        $this->resetMemoizedSiswa();
    }

    public function updatedKelasId(): void
    {
        $this->selectedSiswaId = $this->kelas_id
            ? Siswa::query()->where('kelas_id', $this->kelas_id)->tanpaArsip()->orderBy('nama')->value('id')
            : null;
        $this->resetMemoizedSiswa();
    }

    public function updatedSelectedSiswaId(): void
    {
        $this->resetMemoizedSiswa();
    }

    public function pilihSiswa(int $siswaId): void
    {
        $siswa = Siswa::query()->tanpaArsip()->find($siswaId);

        if ($siswa) {
            $this->selectedSiswaId = $siswa->id;
            $this->resetMemoizedSiswa();
        }
    }

    public function setStatusFilter(string $status): void
    {
        $this->status_filter = $status;
    }

    public function resetFilterTabel(): void
    {
        $this->status_filter = 'semua';
        $this->cari_pos = '';
    }

    protected function resetMemoizedSiswa(): void
    {
        $this->cachedSiswa = null;
        $this->cachedSiswaId = null;
    }

    /**
     * @return array<int, string>
     */
    public function getKelasOptionsProperty(): array
    {
        return Kelas::query()
            ->with(['tahunAjaran', 'jurusan'])
            ->orderByDesc('tahun_ajaran_id')
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get()
            ->mapWithKeys(fn (Kelas $kelas): array => [
                $kelas->id => trim(($kelas->tahunAjaran?->nama ? $kelas->tahunAjaran->nama.' — ' : '').$kelas->nama_kelas),
            ])
            ->all();
    }

    /**
     * Students shown by the active selection method.
     *
     * @return Collection<int, Siswa>
     */
    public function getSiswaHasilProperty(): Collection
    {
        if ($this->cara_pilih === 'kelas') {
            if (! $this->kelas_id) {
                return collect();
            }

            return Siswa::query()
                ->with('kelas')
                ->where('kelas_id', $this->kelas_id)
                ->tanpaArsip()
                ->orderBy('nama')
                ->get();
        }

        $kataKunci = trim($this->cari);

        if (mb_strlen($kataKunci) < 2) {
            return collect();
        }

        return Siswa::query()
            ->with('kelas')
            ->tanpaArsip()
            ->where(function ($query) use ($kataKunci): void {
                $query->where('nama', 'like', "%{$kataKunci}%")
                    ->orWhere('nisn', 'like', "%{$kataKunci}%")
                    ->orWhere('nis', 'like', "%{$kataKunci}%");
            })
            ->orderBy('nama')
            ->limit(20)
            ->get();
    }

    protected ?Siswa $cachedSiswa = null;

    protected ?int $cachedSiswaId = null;

    public function getSiswaProperty(): ?Siswa
    {
        if (! $this->selectedSiswaId) {
            return null;
        }

        if ($this->cachedSiswaId === $this->selectedSiswaId && $this->cachedSiswa !== null) {
            return $this->cachedSiswa;
        }

        $this->cachedSiswaId = $this->selectedSiswaId;

        return $this->cachedSiswa = Siswa::with(['kelas.jurusan', 'spp'])->find($this->selectedSiswaId);
    }

    /**
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     totalNominal: int,
     *     totalDibayar: int,
     *     totalSisa: int,
     *     totalDitanggung: int,
     *     countSemua: int,
     *     countLunas: int,
     *     countBelumLunas: int,
     *     countSebagian: int
     * }
     */
    public function getTagihanDataProperty(): array
    {
        $siswa = $this->siswa;
        if (! $siswa) {
            return [
                'items' => [],
                'totalNominal' => 0,
                'totalDibayar' => 0,
                'totalSisa' => 0,
                'totalDitanggung' => 0,
                'countSemua' => 0,
                'countLunas' => 0,
                'countBelumLunas' => 0,
                'countSebagian' => 0,
            ];
        }

        $tanggunganPemerintah = app(TanggunganPemerintahService::class);
        $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);
        $activeTahunAjaran = $siswa->spp?->tahun_ajaran;

        $riwayatPembayaran = $siswa->pembayarans()
            ->whereNull('void_at')
            ->with(['user', 'spp'])
            ->get();

        $nominalSpp = (int) ($siswa->spp?->nominal ?? 0);
        $items = [];

        // 1. Tagihan SPP Bulanan (12 Bulan Tahun Pelajaran)
        foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
            $bulan = $periode['bulan'];
            $tahun = $periode['tahun'];

            $bayarList = $riwayatPembayaran
                ->where('spp_id', $siswa->spp_id)
                ->where('bulan_dibayar', $bulan);

            $dibayar = (int) $bayarList->sum('jumlah_bayar');
            $ditanggung = $tanggunganPemerintah->isSppDitanggung($siswa, $siswa->spp, $bulan, $tahun);
            $sisa = $ditanggung ? 0 : max(0, $nominalSpp - $dibayar);
            $status = $ditanggung ? 'ditanggung' : ($dibayar <= 0 ? 'belum' : ($sisa > 0 ? 'sebagian' : 'lunas'));

            $latestPayment = $bayarList->sortByDesc('tgl_bayar')->first();
            $tanggalBayarFormatted = null;
            if ($bayarList->isNotEmpty() && $latestPayment?->tgl_bayar) {
                $tanggalBayarFormatted = $latestPayment->tgl_bayar->format('d/m/Y');
                if ($bayarList->count() > 1) {
                    $tanggalBayarFormatted .= ' ('.$bayarList->count().'x bayar)';
                }
            }

            $items[] = [
                'id' => 'spp_'.$bulan.'_'.$tahun,
                'pos_tarif' => 'SPP Bulan '.$bulan.' '.$tahun,
                'kategori' => 'SPP',
                'tipe' => 'Bulanan',
                'nominal' => $nominalSpp,
                'dibayar' => min($dibayar, $nominalSpp),
                'sisa' => $sisa,
                'status' => $status,
                'lunas' => $status === 'lunas',
                'ditanggung' => $ditanggung,
                'tanggal_bayar' => $tanggalBayarFormatted,
                'pembayaran_terakhir' => $latestPayment,
                'jumlah_transaksi' => $bayarList->count(),
                'bulan' => $bulan,
                'tahun' => $tahun,
            ];
        }

        // 2. Tagihan Biaya Non-SPP (Uang Gedung, Seragam, Ujian, Praktik, dll.)
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

        foreach ($nonSppTarifs as $tarif) {
            $bayarList = $riwayatPembayaran->where('spp_id', $tarif->id);
            $dibayar = (int) $bayarList->sum('jumlah_bayar');
            $ditanggung = $tanggunganPemerintah->isPosDitanggung($siswa, $tarif);
            $sisa = $ditanggung ? 0 : max(0, $tarif->nominal - $dibayar);
            $status = $ditanggung ? 'ditanggung' : ($dibayar <= 0 ? 'belum' : ($sisa > 0 ? 'sebagian' : 'lunas'));

            $latestPayment = $bayarList->sortByDesc('tgl_bayar')->first();
            $tanggalBayarFormatted = null;
            if ($bayarList->isNotEmpty() && $latestPayment?->tgl_bayar) {
                $tanggalBayarFormatted = $latestPayment->tgl_bayar->format('d/m/Y');
                if ($bayarList->count() > 1) {
                    $tanggalBayarFormatted .= ' ('.$bayarList->count().'x bayar)';
                }
            }

            $items[] = [
                'id' => 'non_spp_'.$tarif->id,
                'pos_tarif' => $tarif->nama ?: $tarif->kategori,
                'kategori' => $tarif->kategori,
                'tipe' => $tarif->tipe ?? 'Sekali Bayar',
                'nominal' => (int) $tarif->nominal,
                'dibayar' => $dibayar,
                'sisa' => $sisa,
                'status' => $status,
                'lunas' => $status === 'lunas',
                'ditanggung' => $ditanggung,
                'tanggal_bayar' => $tanggalBayarFormatted,
                'pembayaran_terakhir' => $latestPayment,
                'jumlah_transaksi' => $bayarList->count(),
                'bulan' => null,
                'tahun' => $tarif->tahun,
            ];
        }

        // Summary calculations
        $totalNominal = 0;
        $totalDibayar = 0;
        $totalSisa = 0;
        $totalDitanggung = 0;
        $countLunas = 0;
        $countBelumLunas = 0;
        $countSebagian = 0;

        foreach ($items as $item) {
            if ($item['ditanggung']) {
                $totalDitanggung += $item['nominal'];
            } else {
                $totalNominal += $item['nominal'];
                $totalDibayar += $item['dibayar'];
                $totalSisa += $item['sisa'];
            }

            if ($item['status'] === 'lunas' || $item['status'] === 'ditanggung') {
                $countLunas++;
            } elseif ($item['status'] === 'sebagian') {
                $countSebagian++;
            } else {
                $countBelumLunas++;
            }
        }

        return [
            'items' => $items,
            'totalNominal' => $totalNominal,
            'totalDibayar' => $totalDibayar,
            'totalSisa' => $totalSisa,
            'totalDitanggung' => $totalDitanggung,
            'countSemua' => count($items),
            'countLunas' => $countLunas,
            'countBelumLunas' => $countBelumLunas,
            'countSebagian' => $countSebagian,
        ];
    }

    /**
     * Items filtered by status pill and table search keyword.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getFilteredItemsProperty(): array
    {
        $items = $this->tagihanData['items'];

        if ($this->status_filter === 'lunas') {
            $items = array_values(array_filter($items, fn (array $i): bool => in_array($i['status'], ['lunas', 'ditanggung'], true)));
        } elseif ($this->status_filter === 'belum_lunas') {
            $items = array_values(array_filter($items, fn (array $i): bool => $i['status'] === 'belum'));
        } elseif ($this->status_filter === 'sebagian') {
            $items = array_values(array_filter($items, fn (array $i): bool => $i['status'] === 'sebagian'));
        }

        $cariPos = trim(mb_strtolower($this->cari_pos));
        if ($cariPos !== '') {
            $items = array_values(array_filter($items, fn (array $i): bool => str_contains(mb_strtolower($i['pos_tarif']), $cariPos) ||
                str_contains(mb_strtolower($i['kategori']), $cariPos) ||
                ($i['tanggal_bayar'] && str_contains(mb_strtolower($i['tanggal_bayar']), $cariPos))
            ));
        }

        return $items;
    }
}
