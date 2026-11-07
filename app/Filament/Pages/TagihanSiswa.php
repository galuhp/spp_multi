<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\Spp;
use App\Support\Pembayaran\TanggunganPemerintahService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class TagihanSiswa extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static UnitEnum|string|null $navigationGroup = 'Portal Siswa';

    protected static ?string $navigationLabel = 'Status Tagihan';

    protected static ?string $title = 'Matriks Tagihan & Status Pembayaran';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.tagihan-siswa';

    public ?int $selectedSiswaId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user?->isSiswa()) {
            return true;
        }

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::PORTAL_SISWA);
    }

    public string $cara_pilih = 'cari';

    public ?int $kelas_id = null;

    public string $cari = '';

    public function mount(): void
    {
        $user = auth()->user();

        if ($user?->isSiswa()) {
            $siswa = $user->siswa ?? Siswa::where('nisn', $user->username)->first();
            $this->selectedSiswaId = $siswa?->id;
        } else {
            $this->selectedSiswaId = Siswa::query()->tanpaArsip()->orderBy('nama')->value('id');
        }
    }

    public function hitungUlangData(): void
    {
        $hasil = Siswa::sinkronkanDataMasuk(true);

        Notification::make()
            ->title('Sinkronisasi Berhasil')
            ->body("Total {$hasil['total']} siswa diperiksa, {$hasil['diperbarui']} data berhasil diperbarui.")
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('hitungUlangData')
                ->label('Hitung Ulang & Sinkronkan Data')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->isStaff() ?? true)
                ->requiresConfirmation()
                ->modalHeading('Hitung Ulang & Sinkronkan Data Siswa')
                ->modalDescription('Tindakan ini akan menghitung ulang tanggal masuk dan tahun masuk seluruh siswa di database agar tunggakan masa lalu dihitung secara akurat.')
                ->action(fn () => $this->hitungUlangData()),
        ];
    }

    public function updatedCaraPilih(): void
    {
        $this->kelas_id = null;
        $this->cari = '';
        $this->selectedSiswaId = null;
    }

    public function updatedKelasId(): void
    {
        $this->selectedSiswaId = $this->kelas_id
            ? Siswa::query()->where('kelas_id', $this->kelas_id)->tanpaArsip()->orderBy('nama')->value('id')
            : null;
    }

    public function pilihSiswa(int $siswaId): void
    {
        $siswa = Siswa::query()->tanpaArsip()->find($siswaId);

        if ($siswa) {
            $this->selectedSiswaId = $siswa->id;
        }
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

    public function getTagihanDataProperty(): array
    {
        $siswa = $this->siswa;
        if (! $siswa) {
            return [
                'bulanStatus' => [],
                'totalLunas' => 0,
                'totalBelumLunas' => 0,
                'nonSppStatus' => [],
                'totalNonSppLunas' => 0,
                'totalNonSppBelumLunas' => 0,
                'totalDitanggung' => 0,
                'totalNonSppDitanggung' => 0,
            ];
        }

        $tanggunganPemerintah = app(TanggunganPemerintahService::class);
        $tahunMulai = Pembayaran::tahunMulaiTarif($siswa->spp);
        $activeTahunAjaran = $siswa->spp?->tahun_ajaran;
        $tahunMasukSiswa = $siswa->tahunMasuk();

        $riwayatPembayaran = $siswa->pembayarans()
            ->whereNull('void_at')
            ->with(['user', 'spp'])
            ->get();

        $bulanStatus = [];
        $totalLunas = 0;
        $totalBelumLunas = 0;
        $totalDitanggung = 0;

        $nominalSpp = (int) ($siswa->spp?->nominal ?? 0);

        foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
            $bulan = $periode['bulan'];
            $tahun = $periode['tahun'];

            if ($tahun < $tahunMasukSiswa || ! $siswa->sudahMasukPadaPeriode($bulan, $tahun)) {
                continue;
            }

            $bayarList = $riwayatPembayaran
                ->where('spp_id', $siswa->spp_id)
                ->where('bulan_dibayar', $bulan);

            $dibayar = (int) $bayarList->sum('jumlah_bayar');
            $ditanggung = $tanggunganPemerintah->isSppDitanggung($siswa, $siswa->spp, $bulan, $tahun);
            $sisa = $ditanggung ? 0 : max(0, $nominalSpp - $dibayar);
            $status = $ditanggung ? 'ditanggung' : ($dibayar <= 0 ? 'belum' : ($sisa > 0 ? 'sebagian' : 'lunas'));

            if ($ditanggung) {
                $totalDitanggung += $nominalSpp;
            } else {
                $totalLunas += min($dibayar, $nominalSpp);
                $totalBelumLunas += $sisa;
            }

            $bulanStatus[] = [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'nominal' => $nominalSpp,
                'dibayar' => min($dibayar, $nominalSpp),
                'sisa' => $sisa,
                'status' => $status,
                'lunas' => $status === 'lunas',
                'ditanggung' => $ditanggung,
                'pembayaran' => $bayarList->sortByDesc('id')->first(),
            ];
        }

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
        $totalNonSppDitanggung = 0;

        foreach ($nonSppTarifs as $tarif) {
            if ($tarif->tahunMulai() < $tahunMasukSiswa) {
                continue;
            }

            $ditanggung = $tanggunganPemerintah->isPosDitanggung($siswa, $tarif);
            $nominal = (int) $tarif->nominal;

            if ($tarif->isBulanan()) {
                foreach (Pembayaran::bulanTahunAjaran($tarif->tahunMulai()) as $periode) {
                    $bulan = $periode['bulan'];
                    $tahun = $periode['tahun'];

                    if ($tahun < $tahunMasukSiswa || ! $siswa->sudahMasukPadaPeriode($bulan, $tahun)) {
                        continue;
                    }

                    $bayarList = $riwayatPembayaran
                        ->where('spp_id', $tarif->id)
                        ->where('bulan_dibayar', $bulan)
                        ->where('tahun_dibayar', $tahun);
                    $dibayar = (int) $bayarList->sum('jumlah_bayar');
                    $sisa = $ditanggung ? 0 : max(0, $nominal - $dibayar);
                    $lunas = ! $ditanggung && $sisa === 0 && $dibayar > 0;

                    if ($ditanggung) {
                        $totalNonSppDitanggung += $nominal;
                    } else {
                        $totalNonSppLunas += min($dibayar, $nominal);
                        $totalNonSppBelumLunas += $sisa;
                    }

                    $nonSppStatus[] = [
                        'tarif' => $tarif,
                        'nominal' => $nominal,
                        'dibayar' => min($dibayar, $nominal),
                        'sisa' => $sisa,
                        'lunas' => $lunas,
                        'ditanggung' => $ditanggung,
                        'pembayarans' => $bayarList,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                    ];
                }

                continue;
            }

            $bayarList = $riwayatPembayaran->where('spp_id', $tarif->id);
            $dibayar = (int) $bayarList->sum('jumlah_bayar');
            $sisa = $ditanggung ? 0 : max(0, $nominal - $dibayar);
            $lunas = ! $ditanggung && $sisa === 0 && $dibayar > 0;

            if ($ditanggung) {
                $totalNonSppDitanggung += $nominal;
            } else {
                $totalNonSppLunas += $dibayar;
                $totalNonSppBelumLunas += $sisa;
            }

            $nonSppStatus[] = [
                'tarif' => $tarif,
                'nominal' => $nominal,
                'dibayar' => $dibayar,
                'sisa' => $sisa,
                'lunas' => $lunas,
                'ditanggung' => $ditanggung,
                'pembayarans' => $bayarList,
                'bulan' => null,
                'tahun' => null,
            ];
        }

        // Tunggakan tahun/tingkat sebelumnya sepanjang timeline siswa
        $tunggakanPrior = [];
        $totalTunggakanPrior = 0;

        $tingkatSekarang = Spp::normalisasiTingkat($siswa->kelas?->tingkat);
        $priorLevels = [];
        if ($tingkatSekarang !== null) {
            $num = (int) $tingkatSekarang;
            if ($num > 10 && $num <= 13) {
                $priorLevels = array_map('strval', range(10, $num - 1));
            } elseif ($num > 7 && $num <= 9) {
                $priorLevels = array_map('strval', range(7, $num - 1));
            } elseif ($num > 1 && $num <= 6) {
                $priorLevels = array_map('strval', range(1, $num - 1));
            }
        }

        $taCurYear = $siswa->kelas?->tahunAjaran ? (int) substr($siswa->kelas->tahunAjaran->nama, 0, 4) : (int) now()->year;

        foreach ($priorLevels as $pLvl) {
            $tarifPrior = Spp::untukTingkat($pLvl);
            $selisih = max(1, (int) $tingkatSekarang - (int) $pLvl);
            $tahunMulaiPrior = $taCurYear - $selisih;

            // Tunggakan hanya dihitung sejak siswa masuk ke sekolah
            if ($tahunMulaiPrior < $tahunMasukSiswa) {
                continue;
            }

            if ($tarifPrior && $tarifPrior->isBulanan()) {
                $nominalPrior = (int) $tarifPrior->nominal;

                foreach (Pembayaran::bulanTahunAjaran($tahunMulaiPrior) as $periode) {
                    $bPrior = $periode['bulan'];
                    $thnPrior = $periode['tahun'];

                    if (! $siswa->sudahMasukPadaPeriode($bPrior, $thnPrior)) {
                        continue;
                    }

                    if ($tanggunganPemerintah->isSppDitanggung($siswa, $tarifPrior, $bPrior, $thnPrior)) {
                        continue;
                    }

                    $dibayarPrior = (int) $riwayatPembayaran
                        ->where('bulan_dibayar', $bPrior)
                        ->where('tahun_dibayar', $thnPrior)
                        ->sum('jumlah_bayar');

                    $sisaPrior = max(0, $nominalPrior - $dibayarPrior);
                    if ($sisaPrior > 0) {
                        $totalTunggakanPrior += $sisaPrior;
                        $tunggakanPrior[] = [
                            'tahun_ajaran' => 'Kelas '.Spp::labelTingkat($pLvl),
                            'bulan' => "SPP {$bPrior} {$thnPrior}",
                            'tipe' => 'SPP',
                            'nominal' => $nominalPrior,
                            'dibayar' => $dibayarPrior,
                            'sisa' => $sisaPrior,
                        ];
                    }
                }
            }

            $nonSppPriorList = Spp::nonSppUntukTingkat($pLvl)
                ->merge(Spp::nonSppGlobalBulanan($tahunMulaiPrior));

            foreach ($nonSppPriorList as $tNonPrior) {
                if ($tNonPrior->tahunMulai() < $tahunMasukSiswa) {
                    continue;
                }

                if ($tNonPrior->isBulanan() && $tNonPrior->tahunMulai() !== $tahunMulaiPrior) {
                    continue;
                }

                if ($tanggunganPemerintah->isPosDitanggung($siswa, $tNonPrior)) {
                    continue;
                }

                $nominalNonPrior = (int) $tNonPrior->nominal;

                if ($tNonPrior->isBulanan()) {
                    foreach (Pembayaran::bulanTahunAjaran($tNonPrior->tahunMulai()) as $periode) {
                        $bulanNonPrior = $periode['bulan'];
                        $tahunNonPrior = $periode['tahun'];

                        if ($tahunNonPrior < $tahunMasukSiswa || ! $siswa->sudahMasukPadaPeriode($bulanNonPrior, $tahunNonPrior)) {
                            continue;
                        }

                        $dibayarNonPrior = (int) $riwayatPembayaran
                            ->where('spp_id', $tNonPrior->id)
                            ->where('bulan_dibayar', $bulanNonPrior)
                            ->where('tahun_dibayar', $tahunNonPrior)
                            ->sum('jumlah_bayar');

                        $sisaNonPrior = max(0, $nominalNonPrior - $dibayarNonPrior);
                        if ($sisaNonPrior <= 0) {
                            continue;
                        }

                        $totalTunggakanPrior += $sisaNonPrior;
                        $tunggakanPrior[] = [
                            'tahun_ajaran' => 'Kelas '.Spp::labelTingkat($pLvl),
                            'bulan' => ($tNonPrior->nama ?: $tNonPrior->kategori)." {$bulanNonPrior} {$tahunNonPrior}",
                            'tipe' => 'Non-SPP',
                            'nominal' => $nominalNonPrior,
                            'dibayar' => min($dibayarNonPrior, $nominalNonPrior),
                            'sisa' => $sisaNonPrior,
                        ];
                    }

                    continue;
                }

                $dibayarNonPrior = (int) $riwayatPembayaran
                    ->where('spp_id', $tNonPrior->id)
                    ->sum('jumlah_bayar');

                $sisaNonPrior = max(0, $nominalNonPrior - $dibayarNonPrior);
                if ($sisaNonPrior > 0) {
                    $totalTunggakanPrior += $sisaNonPrior;
                    $tunggakanPrior[] = [
                        'tahun_ajaran' => 'Kelas '.Spp::labelTingkat($pLvl),
                        'bulan' => $tNonPrior->nama ?: $tNonPrior->kategori,
                        'tipe' => 'Non-SPP',
                        'nominal' => $nominalNonPrior,
                        'dibayar' => $dibayarNonPrior,
                        'sisa' => $sisaNonPrior,
                    ];
                }
            }
        }

        $totalBelumLunas += $totalTunggakanPrior;

        return [
            'bulanStatus' => $bulanStatus,
            'totalLunas' => $totalLunas,
            'totalBelumLunas' => $totalBelumLunas,
            'tunggakanPrior' => $tunggakanPrior,
            'totalTunggakanPrior' => $totalTunggakanPrior,
            'nonSppStatus' => $nonSppStatus,
            'totalNonSppLunas' => $totalNonSppLunas,
            'totalNonSppBelumLunas' => $totalNonSppBelumLunas,
            'totalDitanggung' => $totalDitanggung,
            'totalNonSppDitanggung' => $totalNonSppDitanggung,
        ];
    }
}
