<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use BackedEnum;
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

    public function getSiswaProperty(): ?Siswa
    {
        if (! $this->selectedSiswaId) {
            return null;
        }

        return Siswa::with(['kelas.jurusan', 'spp'])->find($this->selectedSiswaId);
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
            ];
        }

        $activeYear = $siswa->spp?->tahun ?? (int) date('Y');
        $activeTahunAjaran = $siswa->spp?->tahun_ajaran;

        $riwayatPembayaran = $siswa->pembayarans()
            ->with(['user', 'spp'])
            ->get();

        $bulanStatus = [];
        $totalLunas = 0;
        $totalBelumLunas = 0;

        foreach (Pembayaran::TAHUN_AJARAN_BULAN_LIST as $bulan) {
            $pembayaran = $riwayatPembayaran->first(function ($p) use ($bulan, $activeYear) {
                return $p->bulan_dibayar === $bulan && $p->tahun_dibayar === $activeYear;
            });

            $lunas = $pembayaran !== null;
            if ($lunas) {
                $totalLunas += $pembayaran->jumlah_bayar;
            } else {
                $totalBelumLunas += ($siswa->spp?->nominal ?? 0);
            }

            $bulanStatus[] = [
                'bulan' => $bulan,
                'tahun' => $activeYear,
                'nominal' => $siswa->spp?->nominal ?? 0,
                'lunas' => $lunas,
                'pembayaran' => $pembayaran,
            ];
        }

        $nonSppTarifs = Spp::where('kategori', '!=', Spp::KATEGORI_SPP)
            ->where(function ($q) use ($activeYear, $activeTahunAjaran) {
                $q->where('tahun', $activeYear)
                    ->orWhere('tahun_ajaran', $activeTahunAjaran);
            })
            ->get();

        $nonSppStatus = [];
        $totalNonSppLunas = 0;
        $totalNonSppBelumLunas = 0;

        foreach ($nonSppTarifs as $tarif) {
            $bayarList = $riwayatPembayaran->where('spp_id', $tarif->id);
            $dibayar = (int) $bayarList->sum('jumlah_bayar');
            $sisa = max(0, $tarif->nominal - $dibayar);
            $lunas = $sisa === 0 && $dibayar > 0;

            $totalNonSppLunas += $dibayar;
            $totalNonSppBelumLunas += $sisa;

            $nonSppStatus[] = [
                'tarif' => $tarif,
                'nominal' => $tarif->nominal,
                'dibayar' => $dibayar,
                'sisa' => $sisa,
                'lunas' => $lunas,
                'pembayarans' => $bayarList,
            ];
        }

        return [
            'bulanStatus' => $bulanStatus,
            'totalLunas' => $totalLunas,
            'totalBelumLunas' => $totalBelumLunas,
            'nonSppStatus' => $nonSppStatus,
            'totalNonSppLunas' => $totalNonSppLunas,
            'totalNonSppBelumLunas' => $totalNonSppBelumLunas,
        ];
    }
}
