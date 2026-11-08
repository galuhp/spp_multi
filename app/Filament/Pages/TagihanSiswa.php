<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\SiswaSubsidi;
use App\Models\Spp;
use App\Support\Pembayaran\SubsidiSiswaService;
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

    protected static ?string $navigationLabel = 'Status Tagihan per siswa';

    protected static bool $shouldRegisterNavigation = false;

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
                'matriksBulanan' => [],
                'totalLunas' => 0,
                'totalBelumLunas' => 0,
                'nonSppStatus' => [],
                'nonSppSekaliBayar' => [],
                'totalNonSppLunas' => 0,
                'totalNonSppBelumLunas' => 0,
                'totalDitanggung' => 0,
                'totalNonSppDitanggung' => 0,
            ];
        }

        $tanggunganPemerintah = app(TanggunganPemerintahService::class);
        $subsidiService = app(SubsidiSiswaService::class);
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
        $totalPotonganSubsidi = 0;

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

            $subsidi = $subsidiService->subsidiUntuk($siswa, $siswa->spp, $bulan, $tahun);
            $persenSubsidi = $subsidi?->persentase ?? 0;
            $wajibBayar = $subsidi ? SiswaSubsidi::hitungKewajiban($nominalSpp, $persenSubsidi) : $nominalSpp;
            $potongan = $nominalSpp - $wajibBayar;
            if (! $ditanggung) {
                $totalPotonganSubsidi += $potongan;
            }

            $sisa = $ditanggung ? 0 : max(0, $wajibBayar - $dibayar);
            $status = $ditanggung ? 'ditanggung' : ($dibayar <= 0 ? 'belum' : ($sisa > 0 ? 'sebagian' : 'lunas'));

            if ($ditanggung) {
                $totalDitanggung += $nominalSpp;
            } else {
                $totalLunas += min($dibayar, $wajibBayar);
                $totalBelumLunas += $sisa;
            }

            $bulanStatus[] = [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'nominal' => $nominalSpp,
                'wajib_bayar' => $wajibBayar,
                'potongan' => $potongan,
                'persen_subsidi' => $persenSubsidi,
                'dibayar' => min($dibayar, $wajibBayar),
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

        $nonSppTarifs = Spp::query()
            ->where(function ($q): void {
                $q->where('kategori', '!=', Spp::KATEGORI_SPP)
                    ->orWhere('tipe', Spp::TIPE_BULANAN);
            })
            ->when($siswa->spp_id, fn ($q, $sppId) => $q->where('id', '!=', $sppId))
            ->where(function ($q) use ($tahunMulai, $activeTahunAjaran) {
                $q->whereIn('tahun', [$tahunMulai, $tahunMulai + 1])
                    ->orWhere('tahun_ajaran', $activeTahunAjaran);
            })
            ->where(function ($q) use ($variasiTingkat): void {
                $q->whereNull('tingkat')->orWhere('tingkat', '');

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

                    $subsidi = $subsidiService->subsidiUntuk($siswa, $tarif, $bulan, $tahun);
                    $persenSubsidi = $subsidi?->persentase ?? 0;
                    $wajibBayar = $subsidi ? SiswaSubsidi::hitungKewajiban($nominal, $persenSubsidi) : $nominal;
                    $potongan = $nominal - $wajibBayar;
                    if (! $ditanggung) {
                        $totalPotonganSubsidi += $potongan;
                    }

                    $bayarList = $riwayatPembayaran
                        ->where('spp_id', $tarif->id)
                        ->where('bulan_dibayar', $bulan)
                        ->where('tahun_dibayar', $tahun);
                    $dibayar = (int) $bayarList->sum('jumlah_bayar');
                    $sisa = $ditanggung ? 0 : max(0, $wajibBayar - $dibayar);
                    $lunas = ! $ditanggung && $sisa === 0 && ($dibayar > 0 || $wajibBayar === 0);

                    if ($ditanggung) {
                        $totalNonSppDitanggung += $nominal;
                    } else {
                        $totalNonSppLunas += min($dibayar, $wajibBayar);
                        $totalNonSppBelumLunas += $sisa;
                    }

                    $nonSppStatus[] = [
                        'tarif' => $tarif,
                        'nominal' => $nominal,
                        'wajib_bayar' => $wajibBayar,
                        'potongan' => $potongan,
                        'persen_subsidi' => $persenSubsidi,
                        'dibayar' => min($dibayar, $wajibBayar),
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

            $subsidi = $subsidiService->subsidiUntuk($siswa, $tarif);
            $persenSubsidi = $subsidi?->persentase ?? 0;
            $wajibBayar = $subsidi ? SiswaSubsidi::hitungKewajiban($nominal, $persenSubsidi) : $nominal;
            $potongan = $nominal - $wajibBayar;
            if (! $ditanggung) {
                $totalPotonganSubsidi += $potongan;
            }

            $bayarList = $riwayatPembayaran->where('spp_id', $tarif->id);
            $dibayar = (int) $bayarList->sum('jumlah_bayar');
            $sisa = $ditanggung ? 0 : max(0, $wajibBayar - $dibayar);
            $lunas = ! $ditanggung && $sisa === 0 && ($dibayar > 0 || $wajibBayar === 0);

            if ($ditanggung) {
                $totalNonSppDitanggung += $nominal;
            } else {
                $totalNonSppLunas += min($dibayar, $wajibBayar);
                $totalNonSppBelumLunas += $sisa;
            }

            $nonSppStatus[] = [
                'tarif' => $tarif,
                'nominal' => $nominal,
                'wajib_bayar' => $wajibBayar,
                'potongan' => $potongan,
                'persen_subsidi' => $persenSubsidi,
                'dibayar' => min($dibayar, $wajibBayar),
                'sisa' => $sisa,
                'lunas' => $lunas,
                'ditanggung' => $ditanggung,
                'pembayarans' => $bayarList,
                'bulan' => null,
                'tahun' => (int) $tarif->tahun,
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

                    $subsidiPrior = $subsidiService->subsidiUntuk($siswa, $tarifPrior, $bPrior, $thnPrior);
                    $persenPrior = $subsidiPrior?->persentase ?? 0;
                    $wajibPrior = $subsidiPrior ? SiswaSubsidi::hitungKewajiban($nominalPrior, $persenPrior) : $nominalPrior;
                    $potonganPrior = $nominalPrior - $wajibPrior;
                    $totalPotonganSubsidi += $potonganPrior;

                    $dibayarPrior = (int) $riwayatPembayaran
                        ->where('bulan_dibayar', $bPrior)
                        ->where('tahun_dibayar', $thnPrior)
                        ->sum('jumlah_bayar');

                    $sisaPrior = max(0, $wajibPrior - $dibayarPrior);
                    if ($sisaPrior > 0) {
                        $totalTunggakanPrior += $sisaPrior;
                        $tunggakanPrior[] = [
                            'tahun_ajaran' => 'Kelas '.Spp::labelTingkat($pLvl),
                            'bulan' => "SPP {$bPrior} {$thnPrior}".($persenPrior > 0 ? " [Subsidi {$persenPrior}%]" : ''),
                            'tipe' => 'SPP',
                            'nominal' => $nominalPrior,
                            'wajib_bayar' => $wajibPrior,
                            'potongan' => $potonganPrior,
                            'persen_subsidi' => $persenPrior,
                            'dibayar' => min($dibayarPrior, $wajibPrior),
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

                        $subsidiNonPrior = $subsidiService->subsidiUntuk($siswa, $tNonPrior, $bulanNonPrior, $tahunNonPrior);
                        $persenNonPrior = $subsidiNonPrior?->persentase ?? 0;
                        $wajibNonPrior = $subsidiNonPrior ? SiswaSubsidi::hitungKewajiban($nominalNonPrior, $persenNonPrior) : $nominalNonPrior;
                        $potonganNonPrior = $nominalNonPrior - $wajibNonPrior;
                        $totalPotonganSubsidi += $potonganNonPrior;

                        $dibayarNonPrior = (int) $riwayatPembayaran
                            ->where('spp_id', $tNonPrior->id)
                            ->where('bulan_dibayar', $bulanNonPrior)
                            ->where('tahun_dibayar', $tahunNonPrior)
                            ->sum('jumlah_bayar');

                        $sisaNonPrior = max(0, $wajibNonPrior - $dibayarNonPrior);
                        if ($sisaNonPrior <= 0) {
                            continue;
                        }

                        $totalTunggakanPrior += $sisaNonPrior;
                        $tunggakanPrior[] = [
                            'tahun_ajaran' => 'Kelas '.Spp::labelTingkat($pLvl),
                            'bulan' => ($tNonPrior->nama ?: $tNonPrior->kategori)." {$bulanNonPrior} {$tahunNonPrior}".($persenNonPrior > 0 ? " [Subsidi {$persenNonPrior}%]" : ''),
                            'tipe' => 'Non-SPP',
                            'nominal' => $nominalNonPrior,
                            'wajib_bayar' => $wajibNonPrior,
                            'potongan' => $potonganNonPrior,
                            'persen_subsidi' => $persenNonPrior,
                            'dibayar' => min($dibayarNonPrior, $wajibNonPrior),
                            'sisa' => $sisaNonPrior,
                        ];
                    }

                    continue;
                }

                $subsidiNonPrior = $subsidiService->subsidiUntuk($siswa, $tNonPrior);
                $persenNonPrior = $subsidiNonPrior?->persentase ?? 0;
                $wajibNonPrior = $subsidiNonPrior ? SiswaSubsidi::hitungKewajiban($nominalNonPrior, $persenNonPrior) : $nominalNonPrior;
                $potonganNonPrior = $nominalNonPrior - $wajibNonPrior;
                $totalPotonganSubsidi += $potonganNonPrior;

                $dibayarNonPrior = (int) $riwayatPembayaran
                    ->where('spp_id', $tNonPrior->id)
                    ->sum('jumlah_bayar');

                $sisaNonPrior = max(0, $wajibNonPrior - $dibayarNonPrior);
                if ($sisaNonPrior > 0) {
                    $totalTunggakanPrior += $sisaNonPrior;
                    $tunggakanPrior[] = [
                        'tahun_ajaran' => 'Kelas '.Spp::labelTingkat($pLvl),
                        'bulan' => ($tNonPrior->nama ?: $tNonPrior->kategori).($persenNonPrior > 0 ? " [Subsidi {$persenNonPrior}%]" : ''),
                        'tipe' => 'Non-SPP',
                        'nominal' => $nominalNonPrior,
                        'wajib_bayar' => $wajibNonPrior,
                        'potongan' => $potonganNonPrior,
                        'persen_subsidi' => $persenNonPrior,
                        'dibayar' => min($dibayarNonPrior, $wajibNonPrior),
                        'sisa' => $sisaNonPrior,
                    ];
                }
            }
        }

        $totalBelumLunas += $totalTunggakanPrior;

        $nonSppSekaliBayar = array_values(array_filter(
            $nonSppStatus,
            fn (array $item): bool => $item['bulan'] === null,
        ));

        return [
            'bulanStatus' => $bulanStatus,
            'matriksBulanan' => $this->susunMatriksBulanan($bulanStatus, $nonSppStatus),
            'totalLunas' => $totalLunas,
            'totalBelumLunas' => $totalBelumLunas,
            'tunggakanPrior' => $tunggakanPrior,
            'totalTunggakanPrior' => $totalTunggakanPrior,
            'nonSppStatus' => $nonSppStatus,
            'nonSppSekaliBayar' => $nonSppSekaliBayar,
            'totalNonSppLunas' => $totalNonSppLunas,
            'totalNonSppBelumLunas' => $totalNonSppBelumLunas,
            'totalDitanggung' => $totalDitanggung,
            'totalNonSppDitanggung' => $totalNonSppDitanggung,
            'totalPotonganSubsidi' => $totalPotonganSubsidi,
        ];
    }

    /**
     * Gabungkan seluruh tarif bulanan (SPP dan pos non-SPP bertipe bulanan) per bulan.
     *
     * @param  array<int, array<string, mixed>>  $bulanStatus
     * @param  array<int, array<string, mixed>>  $nonSppStatus
     * @return array<int, array<string, mixed>>
     */
    protected function susunMatriksBulanan(array $bulanStatus, array $nonSppStatus): array
    {
        $matriks = [];

        $urutanBulanan = function (string $bulan, int $tahun): int {
            $indeks = array_search($bulan, Pembayaran::TAHUN_AJARAN_BULAN_LIST, true);
            $indeks = $indeks === false ? 0 : $indeks;
            $tahunAjaran = $indeks >= 6 ? $tahun - 1 : $tahun;

            return ($tahunAjaran * 12) + $indeks;
        };

        foreach ($bulanStatus as $item) {
            $kunci = "{$item['bulan']}|{$item['tahun']}";

            $matriks[$kunci] ??= [
                'bulan' => $item['bulan'],
                'tahun' => $item['tahun'],
                'urutan' => $urutanBulanan($item['bulan'], (int) $item['tahun']),
                'spp' => null,
                'pos_lain' => [],
            ];

            $matriks[$kunci]['spp'] = $item;
        }

        foreach ($nonSppStatus as $item) {
            if ($item['bulan'] === null) {
                continue;
            }

            $kunci = "{$item['bulan']}|{$item['tahun']}";

            $matriks[$kunci] ??= [
                'bulan' => $item['bulan'],
                'tahun' => (int) $item['tahun'],
                'urutan' => $urutanBulanan($item['bulan'], (int) $item['tahun']),
                'spp' => null,
                'pos_lain' => [],
            ];

            $ditanggung = (bool) $item['ditanggung'];
            $dibayar = (int) $item['dibayar'];
            $sisa = (int) $item['sisa'];

            $matriks[$kunci]['pos_lain'][] = [
                'nama' => $item['tarif']->nama ?: $item['tarif']->kategori,
                'kategori' => $item['tarif']->kategori,
                'nominal' => (int) $item['nominal'],
                'wajib_bayar' => (int) ($item['wajib_bayar'] ?? $item['nominal']),
                'potongan' => (int) ($item['potongan'] ?? 0),
                'persen_subsidi' => (int) ($item['persen_subsidi'] ?? 0),
                'dibayar' => $dibayar,
                'sisa' => $sisa,
                'status' => $ditanggung ? 'ditanggung' : ($sisa === 0 && ($dibayar > 0 || ($item['wajib_bayar'] ?? $item['nominal']) === 0) ? 'lunas' : ($dibayar > 0 ? 'sebagian' : 'belum')),
                'lunas' => (bool) $item['lunas'],
                'ditanggung' => $ditanggung,
                'pembayaran' => $item['pembayarans']->sortByDesc('id')->first(),
            ];
        }

        foreach ($matriks as $kunci => $bulan) {
            $pos = [];

            if ($bulan['spp'] !== null) {
                $pos[] = $bulan['spp'];
            }

            $pos = array_merge($pos, $bulan['pos_lain']);

            $total = array_sum(array_map(fn (array $baris): int => (int) $baris['nominal'], $pos));
            $totalDibayar = array_sum(array_map(fn (array $baris): int => (int) $baris['dibayar'], $pos));
            $totalSisa = array_sum(array_map(fn (array $baris): int => (int) $baris['sisa'], $pos));
            $lunas = array_reduce($pos, fn (bool $bawa, array $baris): bool => $bawa && ($baris['lunas'] || $baris['ditanggung']), true);
            $ditanggung = array_reduce($pos, fn (bool $bawa, array $baris): bool => $bawa && $baris['ditanggung'], true);
            $sebagian = ! $lunas && array_reduce($pos, fn (bool $bawa, array $baris): bool => $bawa || $baris['dibayar'] > 0 || $baris['ditanggung'], false);

            $matriks[$kunci]['total'] = $total;
            $matriks[$kunci]['total_dibayar'] = $totalDibayar;
            $matriks[$kunci]['total_sisa'] = $totalSisa;
            $matriks[$kunci]['lunas'] = $lunas;
            $matriks[$kunci]['ditanggung'] = $ditanggung;
            $matriks[$kunci]['sebagian'] = $sebagian;
        }

        $matriks = array_values($matriks);

        usort($matriks, fn (array $a, array $b): int => $a['urutan'] <=> $b['urutan']);

        return $matriks;
    }
}
