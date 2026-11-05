<?php

namespace App\Support\Pembayaran;

use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\PembayaranGabunganBaris;
use App\Models\Siswa;
use App\Models\Spp;

class PembayaranService
{
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
        $tarif = $siswa->spp;

        if ($tarif) {
            if ($tarif->isBulanan()) {
                $tahunMulai = Pembayaran::tahunMulaiTarif($tarif);

                foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
                    $bulan = $periode['bulan'];
                    $tahun = $periode['tahun'];

                    $sudah = Pembayaran::query()
                        ->where('siswa_id', $siswa->id)
                        ->where('spp_id', $tarif->id)
                        ->where('bulan_dibayar', $bulan)
                        ->whereNull('void_at')
                        ->exists();

                    if ($sudah) {
                        continue;
                    }

                    $items[] = [
                        'key' => "spp:{$tarif->id}:{$bulan}:{$tahun}",
                        'spp_id' => $tarif->id,
                        'bulan' => $bulan,
                        'tahun' => $tahun,
                        'nominal' => (int) $tarif->nominal,
                        'sisa' => (int) $tarif->nominal,
                        'label' => "SPP {$bulan} {$tahun}",
                        'tipe' => 'spp',
                    ];
                }
            } else {
                $dibayar = $this->totalDibayar($siswa, $tarif);
                $sisa = max(0, (int) $tarif->nominal - $dibayar);

                if ($sisa > 0) {
                    $items[] = [
                        'key' => "spp:{$tarif->id}",
                        'spp_id' => $tarif->id,
                        'bulan' => null,
                        'tahun' => (int) $tarif->tahun,
                        'nominal' => (int) $tarif->nominal,
                        'sisa' => $sisa,
                        'label' => $tarif->namaLengkap(),
                        'tipe' => 'non_spp',
                    ];
                }
            }
        }

        $tahunTarif = $tarif?->tahun;
        $tahunAjaran = $tarif?->tahun_ajaran;

        $nonSpp = Spp::query()
            ->where('kategori', '!=', Spp::KATEGORI_SPP)
            ->when($tahunTarif || $tahunAjaran, function ($query) use ($tahunTarif, $tahunAjaran): void {
                $query->where(function ($subquery) use ($tahunTarif, $tahunAjaran): void {
                    if ($tahunTarif) {
                        $subquery->where('tahun', $tahunTarif);
                    }

                    if ($tahunAjaran) {
                        $subquery->orWhere('tahun_ajaran', $tahunAjaran);
                    }
                });
            })
            ->orderBy('id')
            ->get();

        foreach ($nonSpp as $tarifLain) {
            if ($tarif && $tarifLain->id === $tarif->id) {
                continue;
            }

            $dibayar = $this->totalDibayar($siswa, $tarifLain);
            $sisa = max(0, (int) $tarifLain->nominal - $dibayar);

            if ($sisa <= 0) {
                continue;
            }

            $items[] = [
                'key' => "spp:{$tarifLain->id}",
                'spp_id' => $tarifLain->id,
                'bulan' => null,
                'tahun' => (int) $tarifLain->tahun,
                'nominal' => (int) $tarifLain->nominal,
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
