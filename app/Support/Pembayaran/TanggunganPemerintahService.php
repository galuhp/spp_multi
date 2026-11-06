<?php

namespace App\Support\Pembayaran;

use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\SiswaTanggunganPemerintah;
use App\Models\Spp;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TanggunganPemerintahService
{
    /**
     * @var array<int, Collection<int, SiswaTanggunganPemerintah>>
     */
    private array $cache = [];

    /**
     * Active markers for a student.
     *
     * @return Collection<int, SiswaTanggunganPemerintah>
     */
    public function aktif(Siswa $siswa): Collection
    {
        return $this->markers($siswa)
            ->filter(fn (SiswaTanggunganPemerintah $tanggungan): bool => $tanggungan->isAktif())
            ->values();
    }

    /**
     * Determine whether an SPP month is covered by the government.
     */
    public function isSppDitanggung(Siswa $siswa, ?Spp $tarif, string $bulan, int $tahun): bool
    {
        if (! $tarif || $tarif->kategori !== Spp::KATEGORI_SPP) {
            return false;
        }

        $bulanIndex = array_search($bulan, Pembayaran::BULAN_LIST, true);

        if ($bulanIndex === false) {
            return false;
        }

        $akhirBulan = Carbon::create($tahun, $bulanIndex + 1, 1)
            ->endOfMonth()
            ->endOfDay();

        return $this->markers($siswa)->contains(
            fn (SiswaTanggunganPemerintah $tanggungan): bool => $tanggungan->isAktifPada($akhirBulan)
                && (
                    $tanggungan->cakupan === SiswaTanggunganPemerintah::CAKUPAN_SPP
                    || (
                        $tanggungan->cakupan === SiswaTanggunganPemerintah::CAKUPAN_POS
                        && (int) $tanggungan->spp_id === (int) $tarif->id
                    )
                ),
        );
    }

    /**
     * Determine whether a specific non-SPP fee is covered by the government.
     */
    public function isPosDitanggung(Siswa $siswa, Spp $tarif): bool
    {
        if ($tarif->kategori === Spp::KATEGORI_SPP) {
            return false;
        }

        return $this->markers($siswa)->contains(
            fn (SiswaTanggunganPemerintah $tanggungan): bool => $tanggungan->cakupan === SiswaTanggunganPemerintah::CAKUPAN_POS
                && (int) $tanggungan->spp_id === (int) $tarif->id
                && $tanggungan->isAktifPada(now()),
        );
    }

    /**
     * Total reference amount currently covered for a student.
     */
    public function totalNominal(Siswa $siswa): int
    {
        return (int) $this->aktif($siswa)
            ->sum(fn (SiswaTanggunganPemerintah $tanggungan): int => $tanggungan->nominal());
    }

    /**
     * Load and cache markers for one student.
     *
     * @return Collection<int, SiswaTanggunganPemerintah>
     */
    private function markers(Siswa $siswa): Collection
    {
        return $this->cache[$siswa->id] ??= $siswa->tanggunganPemerintah()
            ->with(['spp', 'siswa.spp'])
            ->orderBy('mulai')
            ->get();
    }
}
