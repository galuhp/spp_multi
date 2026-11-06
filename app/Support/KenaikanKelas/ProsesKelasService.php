<?php

namespace App\Support\KenaikanKelas;

use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\ProsesKelasBatch;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProsesKelasService
{
    public const TINGKAT_AKHIR = ['12', '13'];

    /**
     * Students eligible for mass promotion from a class.
     *
     * @return Collection<int, Siswa>
     */
    public function siswaKelas(int $kelasId): Collection
    {
        return Siswa::query()
            ->where('kelas_id', $kelasId)
            ->aktif()
            ->orderBy('nama')
            ->get();
    }

    /**
     * Map a level to the next level. Returns null for final levels.
     */
    public function tingkatBerikutnya(?string $tingkat): ?string
    {
        return match ((string) $tingkat) {
            '10', 'X' => '11',
            '11', 'XI' => '12',
            default => null,
        };
    }

    /**
     * Normalize a level to the numeric form used by the app (10/11/12).
     */
    public function normalisasiTingkat(?string $tingkat): ?string
    {
        return match ((string) $tingkat) {
            'X' => '10',
            'XI' => '11',
            'XII' => '12',
            default => $tingkat === null || $tingkat === '' ? null : (string) $tingkat,
        };
    }

    /**
     * Determine the default target class (next level, same department).
     */
    public function kelasTujuanDefault(Kelas $kelasAsal, int $tahunAjaranTujuanId): ?Kelas
    {
        $tingkatTujuan = $this->tingkatBerikutnya($this->normalisasiTingkat($kelasAsal->tingkat));

        if ($tingkatTujuan === null) {
            return null;
        }

        $kandidat = Kelas::query()
            ->where('tahun_ajaran_id', $tahunAjaranTujuanId)
            ->where('jurusan_id', $kelasAsal->jurusan_id)
            ->whereIn('tingkat', [$tingkatTujuan, $this->formatTingkat($tingkatTujuan)])
            ->get();

        return $kandidat->count() === 1 ? $kandidat->first() : null;
    }

    /**
     * Format a numeric level into the alternative notation.
     */
    public function formatTingkat(string $tingkat): string
    {
        return match ($tingkat) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            default => $tingkat,
        };
    }

    /**
     * Execute a mass promotion.
     *
     * @param  array<int, array{status: string, kelas_tujuan_id: int}>  $keputusan
     * @return array{batch: ProsesKelasBatch, diproses: int, dilewati: array<int, string>, pesan: array<int, string>}
     */
    public function naikKelas(
        int $tahunAjaranAsalId,
        int $tahunAjaranTujuanId,
        int $kelasAsalId,
        array $keputusan,
        int $userId,
    ): array {
        $this->pastikanStaf();

        if ($tahunAjaranAsalId === $tahunAjaranTujuanId) {
            throw new RuntimeException('Tahun ajaran asal dan tujuan tidak boleh sama.');
        }

        $kelasAsal = Kelas::query()->findOrFail($kelasAsalId);

        if ((int) $kelasAsal->tahun_ajaran_id !== $tahunAjaranAsalId) {
            throw new RuntimeException('Kelas asal tidak berada pada tahun ajaran asal yang dipilih.');
        }

        $siswaKelas = $this->siswaKelas($kelasAsalId)->keyBy('id');

        $dilewati = [];
        $pesan = [];
        $siap = [];

        foreach ($keputusan as $siswaId => $baris) {
            $siswa = $siswaKelas->get((int) $siswaId);

            if (! $siswa) {
                $dilewati[] = "Siswa #{$siswaId} tidak aktif atau bukan anggota kelas asal.";

                continue;
            }

            $status = $baris['status'] ?? 'naik';
            $kelasTujuanId = (int) ($baris['kelas_tujuan_id'] ?? 0);

            if (! in_array($status, [RiwayatKelas::STATUS_NAIK, RiwayatKelas::STATUS_TINGGAL], true)) {
                $dilewati[] = "Status untuk {$siswa->nama} tidak valid.";

                continue;
            }

            if ($status === RiwayatKelas::STATUS_NAIK && in_array($this->normalisasiTingkat($kelasAsal->tingkat), self::TINGKAT_AKHIR, true)) {
                $pesan[] = "{$siswa->nama} berada di tingkat akhir dan tidak dapat naik kelas. Gunakan fitur Kelulusan.";

                continue;
            }

            $kelasTujuan = Kelas::query()->find($kelasTujuanId);

            if (! $kelasTujuan || (int) $kelasTujuan->tahun_ajaran_id !== $tahunAjaranTujuanId) {
                $dilewati[] = "Kelas tujuan untuk {$siswa->nama} tidak valid pada tahun ajaran tujuan.";

                continue;
            }

            $tingkatAsal = $this->normalisasiTingkat($kelasAsal->tingkat);
            $tingkatTujuan = $this->normalisasiTingkat($kelasTujuan->tingkat);
            $tingkatDiharapkan = $status === RiwayatKelas::STATUS_NAIK
                ? $this->tingkatBerikutnya($tingkatAsal)
                : $tingkatAsal;

            if ($tingkatTujuan !== $tingkatDiharapkan) {
                $dilewati[] = "Kelas tujuan {$kelasTujuan->nama_kelas} tidak sesuai tingkat untuk {$siswa->nama}.";

                continue;
            }

            if (RiwayatKelas::query()
                ->where('siswa_id', $siswa->id)
                ->where('tahun_ajaran_id', $tahunAjaranTujuanId)
                ->exists()) {
                $dilewati[] = "{$siswa->nama} sudah memiliki riwayat kelas pada tahun ajaran tujuan dan dilewati.";

                continue;
            }

            $siap[] = [
                'siswa' => $siswa,
                'status' => $status,
                'kelas_tujuan' => $kelasTujuan,
            ];
        }

        if ($siap === []) {
            return [
                'batch' => null,
                'diproses' => 0,
                'dilewati' => $dilewati,
                'pesan' => $pesan,
            ];
        }

        $tahunAjaranTujuanNama = TahunAjaran::query()->whereKey($tahunAjaranTujuanId)->value('nama');

        $batch = DB::transaction(function () use ($siap, $kelasAsal, $tahunAjaranAsalId, $tahunAjaranTujuanId, $tahunAjaranTujuanNama, $userId, $dilewati, $pesan) {
            $batch = ProsesKelasBatch::create([
                'jenis' => ProsesKelasBatch::JENIS_NAIK_KELAS,
                'tahun_ajaran_asal' => $tahunAjaranAsalId,
                'tahun_ajaran_tujuan' => $tahunAjaranTujuanId,
                'dibuat_oleh' => $userId,
                'jumlah_siswa' => count($siap),
                'status' => ProsesKelasBatch::STATUS_SELESAI,
            ]);

            foreach ($siap as $baris) {
                /** @var Siswa $siswa */
                $siswa = $baris['siswa'];
                /** @var Kelas $kelasTujuan */
                $kelasTujuan = $baris['kelas_tujuan'];

                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $tahunAjaranAsalId,
                    ],
                    [
                        'kelas_id' => $kelasAsal->id,
                        'status' => $baris['status'],
                        'batch_id' => $batch->id,
                    ]
                );

                RiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $kelasTujuan->id,
                    'tahun_ajaran_id' => $tahunAjaranTujuanId,
                    'status' => RiwayatKelas::STATUS_AKTIF,
                    'batch_id' => $batch->id,
                ]);

                $siswa->update(['kelas_id' => $kelasTujuan->id]);

                $tarifTujuan = Spp::untukTingkat($kelasTujuan->tingkat, $tahunAjaranTujuanNama);

                if ($tarifTujuan) {
                    $siswa->update(['spp_id' => $tarifTujuan->id]);
                }
            }

            LogAktivitas::catat(
                'kenaikan_kelas.dieksekusi',
                "Kenaikan kelas massal: {$batch->jumlah_siswa} siswa diproses.",
                $batch,
                [
                    'batch_id' => $batch->id,
                    'tahun_ajaran_asal' => $tahunAjaranAsalId,
                    'tahun_ajaran_tujuan' => $tahunAjaranTujuanId,
                    'jumlah_siswa' => $batch->jumlah_siswa,
                    'dilewati' => $dilewati,
                    'peringatan' => $pesan,
                ]
            );

            return $batch;
        });

        return [
            'batch' => $batch,
            'diproses' => count($siap),
            'dilewati' => $dilewati,
            'pesan' => $pesan,
        ];
    }

    /**
     * Execute mass graduation.
     *
     * @param  array<int, int>  $siswaIds
     * @return array{batch: ProsesKelasBatch, diproses: int, dilewati: array<int, string>}
     */
    public function luluskan(
        int $tahunAjaranId,
        int $kelasId,
        array $siswaIds,
        string $tanggalLulus,
        int $userId,
    ): array {
        $this->pastikanStaf();

        $kelas = Kelas::query()->findOrFail($kelasId);

        if ((int) $kelas->tahun_ajaran_id !== $tahunAjaranId) {
            throw new RuntimeException('Kelas tidak berada pada tahun ajaran yang dipilih.');
        }

        if (! in_array($this->normalisasiTingkat($kelas->tingkat), self::TINGKAT_AKHIR, true)) {
            throw new RuntimeException('Kelulusan hanya dapat dilakukan untuk kelas tingkat akhir.');
        }

        $siswaKelas = $this->siswaKelas($kelasId)->keyBy('id');
        $dilewati = [];
        $siap = [];

        foreach ($siswaIds as $siswaId) {
            $siswa = $siswaKelas->get((int) $siswaId);

            if (! $siswa) {
                $dilewati[] = "Siswa #{$siswaId} tidak aktif atau bukan anggota kelas ini.";

                continue;
            }

            $siap[] = $siswa;
        }

        if ($siap === []) {
            return ['batch' => null, 'diproses' => 0, 'dilewati' => $dilewati];
        }

        $batch = DB::transaction(function () use ($siap, $tahunAjaranId, $tanggalLulus, $userId, $dilewati) {
            $batch = ProsesKelasBatch::create([
                'jenis' => ProsesKelasBatch::JENIS_KELULUSAN,
                'tahun_ajaran_asal' => $tahunAjaranId,
                'tahun_ajaran_tujuan' => null,
                'dibuat_oleh' => $userId,
                'jumlah_siswa' => count($siap),
                'status' => ProsesKelasBatch::STATUS_SELESAI,
            ]);

            foreach ($siap as $siswa) {
                RiwayatKelas::query()
                    ->where('siswa_id', $siswa->id)
                    ->where('tahun_ajaran_id', $tahunAjaranId)
                    ->update([
                        'status' => RiwayatKelas::STATUS_LULUS,
                        'batch_id' => $batch->id,
                    ]);

                $siswa->update([
                    'status' => 'lulus',
                    'is_archived' => true,
                    'archived_at' => now(),
                    'tanggal_lulus' => $tanggalLulus,
                ]);
            }

            LogAktivitas::catat(
                'kelulusan.dieksekusi',
                "Kelulusan massal: {$batch->jumlah_siswa} siswa diluluskan dan diarsipkan.",
                $batch,
                [
                    'batch_id' => $batch->id,
                    'tahun_ajaran' => $tahunAjaranId,
                    'tanggal_lulus' => $tanggalLulus,
                    'jumlah_siswa' => $batch->jumlah_siswa,
                    'dilewati' => $dilewati,
                ]
            );

            return $batch;
        });

        return ['batch' => $batch, 'diproses' => count($siap), 'dilewati' => $dilewati];
    }

    /**
     * Cancel a promotion batch when no payment has been recorded for the new rows.
     *
     * @return array{berhasil: bool, alasan: ?string}
     */
    public function batalkan(ProsesKelasBatch $batch): array
    {
        $this->pastikanStaf();

        if ($batch->status === ProsesKelasBatch::STATUS_DIBATALKAN) {
            return ['berhasil' => false, 'alasan' => 'Batch ini sudah dibatalkan.'];
        }

        if ($batch->jenis !== ProsesKelasBatch::JENIS_NAIK_KELAS) {
            return ['berhasil' => false, 'alasan' => 'Pembatalan hanya tersedia untuk batch kenaikan kelas.'];
        }

        $tahunTujuan = $batch->tahunAjaranTujuan;
        $tahunMulai = $tahunTujuan?->tanggal_mulai?->year ?? (int) substr((string) $tahunTujuan?->nama, 0, 4);

        $adaPembayaran = Pembayaran::query()
            ->whereIn('siswa_id', RiwayatKelas::query()
                ->where('batch_id', $batch->id)
                ->pluck('siswa_id'))
            ->where(function ($query) use ($batch, $tahunMulai) {
                $query->where('tahun_dibayar', '>=', $tahunMulai)
                    ->orWhere('created_at', '>=', $batch->created_at);
            })
            ->exists();

        if ($adaPembayaran) {
            return ['berhasil' => false, 'alasan' => 'Batch tidak dapat dibatalkan karena sudah ada pembayaran pada tahun ajaran tujuan.'];
        }

        $tahunAjaranAsalNama = TahunAjaran::query()->whereKey($batch->tahun_ajaran_asal)->value('nama');

        DB::transaction(function () use ($batch, $tahunAjaranAsalNama) {
            $riwayatBaru = RiwayatKelas::query()->where('batch_id', $batch->id)->get();

            foreach ($riwayatBaru as $riwayat) {
                if ((int) $riwayat->tahun_ajaran_id !== (int) $batch->tahun_ajaran_tujuan) {
                    continue;
                }

                RiwayatKelas::query()
                    ->where('siswa_id', $riwayat->siswa_id)
                    ->where('tahun_ajaran_id', $batch->tahun_ajaran_asal)
                    ->update(['status' => RiwayatKelas::STATUS_AKTIF, 'batch_id' => null]);

                $siswa = Siswa::query()->find($riwayat->siswa_id);
                $kelasAsal = RiwayatKelas::query()
                    ->where('siswa_id', $riwayat->siswa_id)
                    ->where('tahun_ajaran_id', $batch->tahun_ajaran_asal)
                    ->value('kelas_id');

                if ($siswa && $kelasAsal) {
                    $perubahan = ['kelas_id' => $kelasAsal];
                    $tarifAsal = Spp::untukTingkat(Kelas::find($kelasAsal)?->tingkat, $tahunAjaranAsalNama);

                    if ($tarifAsal) {
                        $perubahan['spp_id'] = $tarifAsal->id;
                    }

                    $siswa->update($perubahan);
                }

                $riwayat->delete();
            }

            $batch->update(['status' => ProsesKelasBatch::STATUS_DIBATALKAN]);

            LogAktivitas::catat(
                'kenaikan_kelas.dibatalkan',
                "Batch kenaikan kelas #{$batch->id} dibatalkan.",
                $batch,
                ['batch_id' => $batch->id, 'jumlah_siswa' => $batch->jumlah_siswa]
            );
        });

        return ['berhasil' => true, 'alasan' => null];
    }

    /**
     * Remove a student from the archive (correction action).
     */
    public function keluarkanDariArsip(Siswa $siswa, string $alasan): void
    {
        $this->pastikanStaf();

        RiwayatKelas::query()
            ->where('siswa_id', $siswa->id)
            ->where('status', RiwayatKelas::STATUS_LULUS)
            ->update([
                'status' => RiwayatKelas::STATUS_AKTIF,
                'batch_id' => null,
            ]);

        $siswa->update([
            'status' => 'aktif',
            'is_archived' => false,
            'archived_at' => null,
            'tanggal_lulus' => null,
        ]);

        LogAktivitas::catat(
            'siswa.keluar_dari_arsip',
            "Siswa {$siswa->nama} dikeluarkan dari arsip. Alasan: {$alasan}",
            $siswa,
            ['alasan' => $alasan]
        );
    }

    /**
     * Outstanding monthly SPP arrears for a student, based on the assigned tariff.
     *
     * @return array{jumlah_bulan: int, nominal: int, bulan: array<int, string>}
     */
    public function tunggakan(Siswa $siswa): array
    {
        $tarif = $siswa->spp;

        if (! $tarif) {
            return ['jumlah_bulan' => 0, 'nominal' => 0, 'bulan' => []];
        }

        $nominal = (int) $tarif->nominal;
        $tahunMulai = Pembayaran::tahunMulaiTarif($tarif);

        $dibayarPerBulan = $siswa->pembayarans()
            ->whereNull('void_at')
            ->where('spp_id', $tarif->id)
            ->whereNotNull('bulan_dibayar')
            ->get(['bulan_dibayar', 'jumlah_bayar'])
            ->groupBy('bulan_dibayar')
            ->map(fn ($rows): int => (int) $rows->sum('jumlah_bayar'));

        $tunggakan = [];
        $totalTunggakan = 0;

        foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
            $sisa = max(0, $nominal - (int) ($dibayarPerBulan->get($periode['bulan']) ?? 0));

            if ($sisa > 0) {
                $tunggakan[] = $periode['bulan'];
                $totalTunggakan += $sisa;
            }
        }

        return [
            'jumlah_bulan' => count($tunggakan),
            'nominal' => $totalTunggakan,
            'bulan' => $tunggakan,
        ];
    }

    /**
     * Check whether a batch can still be cancelled.
     */
    public function bisaDibatalkan(ProsesKelasBatch $batch): bool
    {
        if ($batch->jenis !== ProsesKelasBatch::JENIS_NAIK_KELAS || $batch->status !== ProsesKelasBatch::STATUS_SELESAI) {
            return false;
        }

        $tahunTujuan = $batch->tahunAjaranTujuan;
        $tahunMulai = $tahunTujuan?->tanggal_mulai?->year ?? (int) substr((string) $tahunTujuan?->nama, 0, 4);

        return ! Pembayaran::query()
            ->whereIn('siswa_id', RiwayatKelas::query()->where('batch_id', $batch->id)->pluck('siswa_id'))
            ->where(function ($query) use ($batch, $tahunMulai) {
                $query->where('tahun_dibayar', '>=', $tahunMulai)
                    ->orWhere('created_at', '>=', $batch->created_at);
            })
            ->exists();
    }

    /**
     * Only administrators may run mass class processes.
     */
    protected function pastikanStaf(): void
    {
        if (! auth()->user()?->isStaff()) {
            throw new RuntimeException('Hanya staf yang dapat menjalankan proses ini.');
        }
    }
}
