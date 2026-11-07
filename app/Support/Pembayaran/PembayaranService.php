<?php

namespace App\Support\Pembayaran;

use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\PembayaranGabunganBaris;
use App\Models\Siswa;
use App\Models\Spp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PembayaranService
{
    public function __construct(
        private readonly TanggunganPemerintahService $tanggunganPemerintah,
    ) {}

    /**
     * Outstanding bills for a student, oldest first.
     *
     * Each item: key, spp_id, bulan, tahun, nominal, sisa, label, tipe.
     *
     * @return array<int, array<string, mixed>>
     */
    public function tagihanTerbuka(Siswa $siswa): array
    {
        $items = [];
        $siswa->loadMissing(['spp', 'kelas', 'riwayatKelas.spp']);

        $tingkatSekarang = Spp::normalisasiTingkat($siswa->kelas?->tingkat);
        $tingkatList = [];
        $taCurYear = $siswa->kelas?->tahunAjaran ? (int) substr($siswa->kelas->tahunAjaran->nama, 0, 4) : 2026;
        $tahunMasuk = $siswa->tahunMasuk();

        if ($tingkatSekarang !== null) {
            $num = (int) $tingkatSekarang;
            $minNum = 10;
            if ($num >= 7 && $num <= 9) {
                $minNum = 7;
            } elseif ($num >= 1 && $num <= 6) {
                $minNum = 1;
            }

            for ($t = $minNum; $t <= $num; $t++) {
                $selisih = $num - $t;
                $tahunMulaiLvl = $taCurYear - $selisih;
                if ($tahunMulaiLvl >= $tahunMasuk) {
                    $tingkatList[] = (string) $t;
                }
            }

            if (empty($tingkatList)) {
                $tingkatList = [(string) $num];
            }
        }

        // Collect all distinct tariffs assigned to the student along their study timeline
        $dibayarPerBulan = Pembayaran::query()
            ->where('siswa_id', $siswa->id)
            ->whereNull('void_at')
            ->whereNotNull('bulan_dibayar')
            ->whereNotNull('tahun_dibayar')
            ->get(['bulan_dibayar', 'tahun_dibayar', 'jumlah_bayar'])
            ->groupBy(fn (Pembayaran $pembayaran): string => $pembayaran->bulan_dibayar.'|'.$pembayaran->tahun_dibayar)
            ->map(fn ($rows): int => (int) $rows->sum('jumlah_bayar'));

        // 1. SPP Bulanan across all timeline levels
        foreach ($tingkatList as $t) {
            $sppLvl = Spp::untukTingkat($t) ?? $siswa->spp;
            if (! $sppLvl || ! $sppLvl->isBulanan()) {
                continue;
            }

            $selisih = max(0, (int) $tingkatSekarang - (int) $t);
            $tahunMulai = $taCurYear - $selisih;

            if ($tahunMulai < $tahunMasuk) {
                continue;
            }

            $nominal = (int) $sppLvl->nominal;
            $isPriorYear = ($t !== $tingkatSekarang);

            foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
                $bulan = $periode['bulan'];
                $tahun = $periode['tahun'];

                if (! $siswa->sudahMasukPadaPeriode($bulan, $tahun)) {
                    continue;
                }

                if ($this->tanggunganPemerintah->isSppDitanggung($siswa, $sppLvl, $bulan, $tahun)) {
                    continue;
                }

                $dibayar = (int) ($dibayarPerBulan->get($bulan.'|'.$tahun) ?? 0);
                $sisa = max(0, $nominal - $dibayar);

                if ($sisa <= 0) {
                    continue;
                }

                $label = $isPriorYear
                    ? "SPP {$bulan} {$tahun} (Kelas ".Spp::labelTingkat($t).")"
                    : "SPP {$bulan} {$tahun}";

                $items[] = [
                    'key' => "spp:{$sppLvl->id}:{$bulan}:{$tahun}",
                    'spp_id' => $sppLvl->id,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'nominal' => $nominal,
                    'dibayar' => min($dibayar, $nominal),
                    'sisa' => $sisa,
                    'label' => $label,
                    'tipe' => 'spp',
                ];
            }
        }

        // 2. Non-SPP across all timeline levels
        $nonSppList = collect();
        foreach ($tingkatList as $t) {
            $selisih = max(0, (int) $tingkatSekarang - (int) $t);
            $tahunMulai = $taCurYear - $selisih;
            if ($tahunMulai < $tahunMasuk) {
                continue;
            }

            foreach (Spp::nonSppUntukTingkat($t) as $tarifNon) {
                $nonSppList->push($tarifNon);
            }
        }

        foreach ($nonSppList->unique('id') as $tarif) {
            if ($tarif->tahunMulai() < $tahunMasuk) {
                continue;
            }

            $dibayar = $this->totalDibayar($siswa, $tarif);
            $nominal = (int) $tarif->nominal;
            $sisa = max(0, $nominal - $dibayar);

            if ($sisa > 0) {
                $labelTingkat = $tarif->tingkat ? " (Kelas ".Spp::labelTingkat($tarif->tingkat).")" : "";
                $items[] = [
                    'key' => "spp:{$tarif->id}",
                    'spp_id' => $tarif->id,
                    'bulan' => null,
                    'tahun' => (int) $tarif->tahun,
                    'nominal' => $nominal,
                    'dibayar' => min($dibayar, $nominal),
                    'sisa' => $sisa,
                    'label' => ($tarif->nama ?: $tarif->kategori).$labelTingkat,
                    'tipe' => 'non_spp',
                ];
            }
        }

        $variasiTingkat = [];
        foreach ($tingkatList as $t) {
            $variasiTingkat[] = $t;
            if (isset(Spp::TINGKAT_LIST[$t])) {
                $variasiTingkat[] = Spp::TINGKAT_LIST[$t];
            }
        }
        $variasiTingkat = array_values(array_unique(array_filter($variasiTingkat)));

        $nonSpp = Spp::query()
            ->where('kategori', '!=', Spp::KATEGORI_SPP)
            ->where(function ($query) use ($variasiTingkat): void {
                $query->whereNull('tingkat');

                if ($variasiTingkat !== []) {
                    $query->orWhereIn('tingkat', $variasiTingkat);
                }
            })
            ->orderBy('tingkat')
            ->orderBy('id')
            ->get();

        $dibayarPerPos = $nonSpp->isEmpty()
            ? []
            : Pembayaran::query()
                ->where('siswa_id', $siswa->id)
                ->whereIn('spp_id', $nonSpp->pluck('id')->all())
                ->whereNull('void_at')
                ->selectRaw('spp_id, SUM(jumlah_bayar) as total')
                ->groupBy('spp_id')
                ->pluck('total', 'spp_id')
                ->map(fn ($total): int => (int) $total)
                ->all();

        foreach ($nonSpp as $tarifLain) {
            if ($tarif && $tarifLain->id === $tarif->id) {
                continue;
            }

            if ($tarifLain->tahunMulai() < $tahunMasuk) {
                continue;
            }

            if ($this->tanggunganPemerintah->isPosDitanggung($siswa, $tarifLain)) {
                continue;
            }

            $dibayar = (int) ($dibayarPerPos[$tarifLain->id] ?? 0);
            $nominal = (int) $tarifLain->nominal;
            $sisa = max(0, $nominal - $dibayar);

            if ($sisa <= 0) {
                continue;
            }

            $items[] = [
                'key' => "spp:{$tarifLain->id}",
                'spp_id' => $tarifLain->id,
                'bulan' => null,
                'tahun' => (int) $tarifLain->tahun,
                'nominal' => $nominal,
                'dibayar' => min($dibayar, $nominal),
                'sisa' => $sisa,
                'label' => $tarifLain->namaLengkap(),
                'tipe' => 'non_spp',
            ];
        }

        return $items;
    }

    /**
     * Total outstanding amount for a student.
     */
    public function totalSisa(Siswa $siswa): int
    {
        return (int) collect($this->tagihanTerbuka($siswa))->sum('sisa');
    }

    /**
     * Allocate an amount to the oldest bills first.
     *
     * @return array{alokasi: array<int, array<string, mixed>>, sisa: int, total_sisa: int, pesan: ?string}
     */
    public function alokasiOtomatis(Siswa $siswa, int $jumlah): array
    {
        $items = $this->tagihanTerbuka($siswa);
        $totalSisa = (int) collect($items)->sum('sisa');

        if ($jumlah <= 0) {
            return ['alokasi' => [], 'sisa' => $totalSisa, 'total_sisa' => $totalSisa, 'pesan' => 'Jumlah harus lebih dari 0.'];
        }

        if ($totalSisa <= 0) {
            return ['alokasi' => [], 'sisa' => 0, 'total_sisa' => 0, 'pesan' => 'Tidak ada tagihan terbuka.'];
        }

        if ($jumlah > $totalSisa) {
            return ['alokasi' => [], 'sisa' => $totalSisa, 'total_sisa' => $totalSisa, 'pesan' => 'Jumlah melebihi total sisa tagihan siswa (Rp '.number_format($totalSisa, 0, ',', '.').').'];
        }

        $tersisa = $jumlah;
        $alokasi = [];

        foreach ($items as $item) {
            if ($tersisa <= 0) {
                break;
            }

            $ambil = min($tersisa, (int) $item['sisa']);

            if ($ambil <= 0) {
                continue;
            }

            $alokasi[] = [
                'tagihan' => $item['key'],
                'spp_id' => $item['spp_id'],
                'bulan' => $item['bulan'],
                'tahun' => $item['tahun'],
                'nominal' => $ambil,
                'label' => $item['label'],
            ];

            $tersisa -= $ambil;
        }

        return ['alokasi' => $alokasi, 'sisa' => $totalSisa, 'total_sisa' => $totalSisa, 'pesan' => null];
    }

    /**
     * Validate a line and return allocation + message.
     *
     * @param  array<int, array<string, mixed>>|null  $alokasiManual
     * @return array{status: string, pesan: ?string, alokasi: array<int, array<string, mixed>>, total_sisa: int}
     */
    public function validasiBaris(Siswa $siswa, int $jumlah, ?array $alokasiManual = null): array
    {
        if ($siswa->is_archived) {
            return ['status' => 'error', 'pesan' => 'Siswa sudah diarsipkan.', 'alokasi' => [], 'total_sisa' => 0];
        }

        if ($siswa->status !== 'aktif') {
            return ['status' => 'error', 'pesan' => 'Siswa tidak berstatus aktif.', 'alokasi' => [], 'total_sisa' => 0];
        }

        if ($jumlah <= 0) {
            return ['status' => 'error', 'pesan' => 'Jumlah harus lebih dari 0.', 'alokasi' => [], 'total_sisa' => $this->totalSisa($siswa)];
        }

        $items = collect($this->tagihanTerbuka($siswa))->keyBy('key');
        $totalSisa = (int) $items->sum('sisa');

        if ($items->isEmpty()) {
            return ['status' => 'error', 'pesan' => 'Tidak ada tagihan terbuka.', 'alokasi' => [], 'total_sisa' => 0];
        }

        if ($jumlah > $totalSisa) {
            return [
                'status' => 'error',
                'pesan' => 'Jumlah melebihi total sisa tagihan siswa (Rp '.number_format($totalSisa, 0, ',', '.').'). Sesuaikan jumlah.',
                'alokasi' => [],
                'total_sisa' => $totalSisa,
            ];
        }

        if ($alokasiManual === null) {
            return [
                'status' => 'ok',
                'pesan' => null,
                'alokasi' => $this->alokasiOtomatis($siswa, $jumlah)['alokasi'],
                'total_sisa' => $totalSisa,
            ];
        }

        $alokasi = [];
        $total = 0;

        foreach ($alokasiManual as $baris) {
            $key = (string) ($baris['tagihan'] ?? '');
            $nominal = (int) ($baris['nominal'] ?? 0);
            $item = $items->get($key);

            if (! $item) {
                return ['status' => 'error', 'pesan' => 'Alokasi manual berisi tagihan yang tidak valid.', 'alokasi' => [], 'total_sisa' => $totalSisa];
            }

            if ($nominal <= 0) {
                continue;
            }

            if ($nominal > (int) $item['sisa']) {
                return ['status' => 'error', 'pesan' => "Alokasi {$item['label']} melebihi sisa tagihan.", 'alokasi' => [], 'total_sisa' => $totalSisa];
            }

            $alokasi[] = [
                'tagihan' => $key,
                'spp_id' => $item['spp_id'],
                'bulan' => $item['bulan'],
                'tahun' => $item['tahun'],
                'nominal' => $nominal,
                'label' => $item['label'],
            ];
            $total += $nominal;
        }

        if ($total !== $jumlah) {
            return ['status' => 'error', 'pesan' => 'Total alokasi manual tidak sama dengan jumlah.', 'alokasi' => $alokasi, 'total_sisa' => $totalSisa];
        }

        return ['status' => 'ok', 'pesan' => null, 'alokasi' => $alokasi, 'total_sisa' => $totalSisa];
    }

    /**
     * Create payments for one student. Must be called inside a transaction.
     *
     * @param  array<int, array<string, mixed>>  $alokasi
     * @return array<int, Pembayaran>
     */
    public function buatPembayaran(
        Siswa $siswa,
        int $jumlah,
        array $alokasi,
        string $metode,
        string $tanggal,
        ?string $catatan,
        int $userId,
        ?PembayaranGabungan $gabungan = null,
        ?PembayaranGabunganBaris $baris = null,
    ): array {
        return Cache::lock("pembayaran-siswa:{$siswa->id}", 10)->block(5, function () use ($siswa, $jumlah, $alokasi, $metode, $tanggal, $catatan, $userId, $gabungan, $baris): array {
            $siswa = Siswa::query()->whereKey($siswa->id)->lockForUpdate()->firstOrFail();

            $validasi = $this->validasiBaris($siswa, $jumlah, $alokasi);

            if ($validasi['status'] === 'error') {
                throw new PembayaranTidakValidException($validasi['pesan'] ?? 'Pembayaran tidak valid.', $baris?->id);
            }

            $kode = Pembayaran::generateKodeTransaksi();
            $dibuat = [];

            foreach ($validasi['alokasi'] as $item) {
                $dibuat[] = Pembayaran::query()->create([
                    'kode_transaksi' => $kode,
                    'user_id' => $userId,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $item['spp_id'],
                    'tgl_bayar' => $tanggal,
                    'bulan_dibayar' => $item['bulan'],
                    'tahun_dibayar' => $item['tahun'],
                    'jumlah_bayar' => $item['nominal'],
                    'metode_pembayaran' => $metode,
                    'catatan' => $catatan,
                    'gabungan_id' => $gabungan?->id,
                    'gabungan_baris_id' => $baris?->id,
                ]);
            }

            return $dibuat;
        });
    }

    /**
     * Void a single payment while preserving the receipt and audit trail.
     */
    public function voidPembayaran(Pembayaran $pembayaran, int $userId, string $alasan): Pembayaran
    {
        return Cache::lock("pembayaran-siswa:{$pembayaran->siswa_id}", 10)->block(5, function () use ($pembayaran, $userId, $alasan): Pembayaran {
            return DB::transaction(function () use ($pembayaran, $userId, $alasan): Pembayaran {
                $terkunci = Pembayaran::query()
                    ->whereKey($pembayaran->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($terkunci->gabungan_baris_id !== null) {
                    throw new \RuntimeException('Pembayaran gabungan harus di-void melalui menu History Pembayaran Gabungan.');
                }

                if ($terkunci->isVoid()) {
                    return $terkunci;
                }

                $terkunci->update([
                    'void_at' => now(),
                    'void_oleh' => $userId,
                    'void_alasan' => $alasan,
                ]);

                LogAktivitas::catat(
                    'pembayaran.void',
                    "Transaksi {$terkunci->kode_transaksi} dibatalkan. Alasan: {$alasan}",
                    $terkunci,
                    [
                        'kode_transaksi' => $terkunci->kode_transaksi,
                        'siswa_id' => $terkunci->siswa_id,
                        'spp_id' => $terkunci->spp_id,
                        'jumlah_bayar' => $terkunci->jumlah_bayar,
                        'alasan' => $alasan,
                    ]
                );

                return $terkunci;
            });
        });
    }

    /**
     * Total non-void payments for a tariff.
     */
    protected function totalDibayar(Siswa $siswa, Spp $tarif): int
    {
        return (int) Pembayaran::query()
            ->where('siswa_id', $siswa->id)
            ->where('spp_id', $tarif->id)
            ->whereNull('void_at')
            ->sum('jumlah_bayar');
    }
}
