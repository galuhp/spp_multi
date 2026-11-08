<?php

namespace App\Support\Pembayaran;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\SiswaSubsidi;
use App\Models\Spp;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class SubsidiSiswaService
{
    /**
     * @var array<int, Collection<int, SiswaSubsidi>>
     */
    private array $cache = [];

    /**
     * Active subsidies for a student.
     *
     * @return Collection<int, SiswaSubsidi>
     */
    public function aktif(Siswa $siswa): Collection
    {
        return $this->subsidies($siswa)
            ->filter(fn (SiswaSubsidi $subsidi): bool => $subsidi->is_aktif)
            ->values();
    }

    /**
     * Resolves matching subsidy for a student and tariff/period.
     */
    public function subsidiUntuk(
        Siswa $siswa,
        ?Spp $tarif,
        ?string $bulan = null,
        ?int $tahun = null,
        ?TahunAjaran $tahunAjaran = null
    ): ?SiswaSubsidi {
        if (! $tarif) {
            return null;
        }

        $tanggalTarget = null;
        if ($bulan && $tahun) {
            $bulanIndex = array_search($bulan, Pembayaran::BULAN_LIST, true);
            if ($bulanIndex !== false) {
                $tanggalTarget = Carbon::create($tahun, $bulanIndex + 1, 1)->endOfMonth()->endOfDay();
            }
        }

        $taId = $tahunAjaran?->id;

        $matching = $this->subsidies($siswa)->filter(function (SiswaSubsidi $sub) use ($tarif, $tanggalTarget, $taId): bool {
            if (! $sub->isAktifPada($tanggalTarget, $taId)) {
                return false;
            }

            if ($sub->cakupan === SiswaSubsidi::CAKUPAN_SEMUA) {
                return true;
            }

            if ($tarif->kategori === Spp::KATEGORI_SPP) {
                return $sub->cakupan === SiswaSubsidi::CAKUPAN_SPP;
            }

            return $sub->cakupan === SiswaSubsidi::CAKUPAN_POS && (int) $sub->spp_id === (int) $tarif->id;
        });

        return $matching->sortByDesc('persentase')->first();
    }

    /**
     * Sisa kewajiban yang wajib dibayar siswa.
     */
    public function kewajibanSiswa(
        Siswa $siswa,
        ?Spp $tarif,
        ?string $bulan = null,
        ?int $tahun = null,
        ?TahunAjaran $tahunAjaran = null
    ): int {
        if (! $tarif) {
            return 0;
        }

        $nominal = (int) $tarif->nominal;
        $subsidi = $this->subsidiUntuk($siswa, $tarif, $bulan, $tahun, $tahunAjaran);

        if (! $subsidi) {
            return $nominal;
        }

        return SiswaSubsidi::hitungKewajiban($nominal, $subsidi->persentase);
    }

    /**
     * Nominal potongan subsidi.
     */
    public function potonganSiswa(
        Siswa $siswa,
        ?Spp $tarif,
        ?string $bulan = null,
        ?int $tahun = null,
        ?TahunAjaran $tahunAjaran = null
    ): int {
        if (! $tarif) {
            return 0;
        }

        $nominal = (int) $tarif->nominal;
        $subsidi = $this->subsidiUntuk($siswa, $tarif, $bulan, $tahun, $tahunAjaran);

        if (! $subsidi) {
            return 0;
        }

        return SiswaSubsidi::hitungPotongan($nominal, $subsidi->persentase);
    }

    /**
     * Persentase subsidi (0 jika tidak ada).
     */
    public function persentaseSiswa(
        Siswa $siswa,
        ?Spp $tarif,
        ?string $bulan = null,
        ?int $tahun = null,
        ?TahunAjaran $tahunAjaran = null
    ): int {
        $subsidi = $this->subsidiUntuk($siswa, $tarif, $bulan, $tahun, $tahunAjaran);

        return $subsidi?->persentase ?? 0;
    }

    /**
     * Load and cache subsidies for one student.
     *
     * @return Collection<int, SiswaSubsidi>
     */
    private function subsidies(Siswa $siswa): Collection
    {
        return $this->cache[$siswa->id] ??= $siswa->subsidies()
            ->with(['spp', 'tahunAjaran'])
            ->get();
    }
}
