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
     * Students eligible for mass promotion from a class in an optional source academic year.
     *
     * @return Collection<int, Siswa>
     */
    public function siswaKelas(int $kelasId, ?int $tahunAjaranId = null): Collection
    {
        return Siswa::query()
            ->with('spp')
            ->where(function ($query) use ($kelasId, $tahunAjaranId): void {
                $query->where('kelas_id', $kelasId);

                if ($tahunAjaranId) {
                    $query->orWhereIn(
                        'id',
                        RiwayatKelas::query()
                            ->where('kelas_id', $kelasId)
                            ->where('tahun_ajaran_id', $tahunAjaranId)
                            ->pluck('siswa_id'),
                    );
                }
            })
            ->aktif()
            ->orderBy('nama')
            ->get();
    }

    /**
     * Map a level to the next level. Supports SD (1-6), SMP (7-9), SMA/SMK (10-12), or Satu Atap (9->10).
     * Returns null when the level is unknown or already at the maximum numeric level.
     */
    public function tingkatBerikutnya(?string $tingkat): ?string
    {
        $normal = $this->normalisasiTingkat($tingkat);

        if ($normal === null || in_array($normal, self::TINGKAT_AKHIR, true)) {
            return null;
        }

        if (is_numeric($normal)) {
            $angka = (int) $normal;

            if ($angka >= 1 && $angka < 12) {
                return (string) ($angka + 1);
            }
        }

        return null;
    }

    /**
     * Normalize a level (Arabic or Roman I-XIII) to its numeric string form.
     */
    public function normalisasiTingkat(?string $tingkat): ?string
    {
        $raw = mb_strtoupper(trim((string) $tingkat));

        return match ($raw) {
            'I' => '1',
            'II' => '2',
            'III' => '3',
            'IV' => '4',
            'V' => '5',
            'VI' => '6',
            'VII' => '7',
            'VIII' => '8',
            'IX' => '9',
            'X' => '10',
            'XI' => '11',
            'XII' => '12',
            'XIII' => '13',
            default => $raw === '' ? null : $raw,
        };
    }

    /**
     * Determine the default target class for a batch promotion:
     * 1. Prefer candidates at the next level (`tingkatBerikutnya`) with the same `jurusan_id` (SMK/SMA).
     * 2. If none match the same `jurusan_id`, fall back to any class at the next level (SD/SMP cross-class).
     * 3. If multiple parallel classes exist (e.g. X RPL 1 -> XI RPL 1, or 1A -> 2A), match by the class suffix/parallel index.
     */
    public function kelasTujuanDefault(Kelas $kelasAsal, int $tahunAjaranTujuanId): ?Kelas
    {
        $tingkatTujuan = $this->tingkatBerikutnya($this->normalisasiTingkat($kelasAsal->tingkat));

        if ($tingkatTujuan === null) {
            return null;
        }

        $variasiTingkat = array_values(array_unique([
            $tingkatTujuan,
            $this->formatTingkat($tingkatTujuan),
        ]));

        $semuaDiTingkat = Kelas::query()
            ->where(function ($query) use ($tahunAjaranTujuanId): void {
                $query->where('tahun_ajaran_id', $tahunAjaranTujuanId)
                    ->orWhereNull('tahun_ajaran_id');
            })
            ->whereIn('tingkat', $variasiTingkat)
            ->orderByRaw('CASE WHEN tahun_ajaran_id = ? THEN 0 ELSE 1 END', [$tahunAjaranTujuanId])
            ->orderBy('nama_kelas')
            ->get();

        // Fallback ke seluruh master kelas pada tingkat tujuan bila rombel sebelumnya diset ke TA lama
        if ($semuaDiTingkat->isEmpty()) {
            $semuaDiTingkat = Kelas::query()
                ->whereIn('tingkat', $variasiTingkat)
                ->orderBy('nama_kelas')
                ->get();
        }

        if ($semuaDiTingkat->isEmpty()) {
            return null;
        }

        // Prioritaskan jurusan yang sama (kasus SMK/SMA)
        $sejurusan = $kelasAsal->jurusan_id
            ? $semuaDiTingkat->where('jurusan_id', $kelasAsal->jurusan_id)->values()
            : collect();

        $kandidat = $sejurusan->isNotEmpty() ? $sejurusan : $semuaDiTingkat;

        if ($kandidat->count() === 1) {
            return $kandidat->first();
        }

        // Coba cocokkan pola nama rombel/paralel (mis. "X RPL 1" -> "XI RPL 1", "1A" -> "2A", "Kelas 1-B" -> "Kelas 2-B")
        $polaDiharapkan = $this->prediksiNamaKelasTujuan($kelasAsal->nama_kelas, $kelasAsal->tingkat, $tingkatTujuan);

        foreach ($kandidat as $calon) {
            $namaCalon = mb_strtoupper(trim((string) $calon->nama_kelas));

            if (in_array($namaCalon, $polaDiharapkan, true)) {
                return $calon;
            }
        }

        // Cocokkan akhiran paralel (angka/huruf di ujung nama kelas, mis. "1", "2", "A", "B")
        $akhiranAsal = $this->ekstrakAkhiranRombel($kelasAsal->nama_kelas);

        if ($akhiranAsal !== null) {
            $cocokAkhiran = $kandidat->first(
                fn (Kelas $calon): bool => $this->ekstrakAkhiranRombel($calon->nama_kelas) === $akhiranAsal
            );

            if ($cocokAkhiran) {
                return $cocokAkhiran;
            }
        }

        return $kandidat->first();
    }

    /**
     * Determine the default target class when a student stays in the same level (tinggal kelas).
     */
    public function kelasTinggalDefault(Kelas $kelasAsal, int $tahunAjaranTujuanId): ?Kelas
    {
        $tingkatAsal = $this->normalisasiTingkat($kelasAsal->tingkat);

        if ($tingkatAsal === null) {
            return null;
        }

        $variasiTingkat = array_values(array_unique([
            $tingkatAsal,
            $this->formatTingkat($tingkatAsal),
        ]));

        $semuaDiTingkat = Kelas::query()
            ->where(function ($query) use ($tahunAjaranTujuanId): void {
                $query->where('tahun_ajaran_id', $tahunAjaranTujuanId)
                    ->orWhereNull('tahun_ajaran_id');
            })
            ->whereIn('tingkat', $variasiTingkat)
            ->orderByRaw('CASE WHEN tahun_ajaran_id = ? THEN 0 ELSE 1 END', [$tahunAjaranTujuanId])
            ->orderBy('nama_kelas')
            ->get();

        if ($semuaDiTingkat->isEmpty()) {
            $semuaDiTingkat = Kelas::query()
                ->whereIn('tingkat', $variasiTingkat)
                ->orderBy('nama_kelas')
                ->get();
        }

        if ($semuaDiTingkat->isEmpty()) {
            return $kelasAsal;
        }

        $sejurusan = $kelasAsal->jurusan_id
            ? $semuaDiTingkat->where('jurusan_id', $kelasAsal->jurusan_id)->values()
            : collect();

        $kandidat = $sejurusan->isNotEmpty() ? $sejurusan : $semuaDiTingkat;

        $namaSama = $kandidat->first(
            fn (Kelas $calon): bool => mb_strtoupper(trim((string) $calon->nama_kelas)) === mb_strtoupper(trim((string) $kelasAsal->nama_kelas))
        );

        return $namaSama ?? $kandidat->first();
    }

    /**
     * Format a numeric level into Roman numeral notation.
     */
    public function formatTingkat(string $tingkat): string
    {
        return match ($this->normalisasiTingkat($tingkat)) {
            '1' => 'I',
            '2' => 'II',
            '3' => 'III',
            '4' => 'IV',
            '5' => 'V',
            '6' => 'VI',
            '7' => 'VII',
            '8' => 'VIII',
            '9' => 'IX',
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            '13' => 'XIII',
            default => $tingkat,
        };
    }

    /**
     * Build expected target class names by replacing the origin level token with the target level token.
     *
     * @return array<int, string>
     */
    protected function prediksiNamaKelasTujuan(string $namaAsal, ?string $tingkatAsal, string $tingkatTujuan): array
    {
        $nama = mb_strtoupper(trim($namaAsal));
        $angkaAsal = (string) $this->normalisasiTingkat($tingkatAsal);
        $romawiAsal = $this->formatTingkat($angkaAsal);
        $angkaTujuan = (string) $this->normalisasiTingkat($tingkatTujuan);
        $romawiTujuan = $this->formatTingkat($angkaTujuan);

        $hasil = [];

        foreach ([$romawiAsal, $angkaAsal] as $tokenAsal) {
            if ($tokenAsal === '') {
                continue;
            }

            $pattern = '/^'.preg_quote($tokenAsal, '/').'(?=[\s\-A-Z0-9]|$)/u';

            if (preg_match($pattern, $nama)) {
                $hasil[] = (string) preg_replace($pattern, $romawiTujuan, $nama, 1);
                $hasil[] = (string) preg_replace($pattern, $angkaTujuan, $nama, 1);
            }
        }

        return array_values(array_unique($hasil));
    }

    /**
     * Extract trailing parallel identifier (e.g. "1" from "X RPL 1" or "A" from "Kelas 1A").
     */
    protected function ekstrakAkhiranRombel(string $namaKelas): ?string
    {
        $nama = mb_strtoupper(trim($namaKelas));

        if (preg_match('/(?:[\s\-]|(?<=\d))([A-Z]|\d+)$/u', $nama, $m)) {
            return $m[1];
        }

        return null;
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

        $namaTahunAsal = (string) TahunAjaran::query()->whereKey($tahunAjaranAsalId)->value('nama');
        $namaTahunTujuan = (string) TahunAjaran::query()->whereKey($tahunAjaranTujuanId)->value('nama');

        if ($namaTahunAsal !== '' && $namaTahunTujuan !== '' && strcmp($namaTahunTujuan, $namaTahunAsal) <= 0) {
            throw new RuntimeException('Tahun ajaran tujuan harus lebih besar dari tahun ajaran asal.');
        }

        $kelasAsal = Kelas::query()->findOrFail($kelasAsalId);

        if ($kelasAsal->tahun_ajaran_id !== null && (int) $kelasAsal->tahun_ajaran_id !== $tahunAjaranAsalId) {
            // Izinkan jika memang ada siswa atau riwayat di kelas ini pada TA asal
            $adaSiswa = $this->siswaKelas($kelasAsalId, $tahunAjaranAsalId)->isNotEmpty();

            if (! $adaSiswa) {
                throw new RuntimeException('Kelas asal tidak berada pada tahun ajaran asal yang dipilih.');
            }
        }

        $siswaKelas = $this->siswaKelas($kelasAsalId, $tahunAjaranAsalId)->keyBy('id');

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

            if (! $kelasTujuan || ($kelasTujuan->tahun_ajaran_id !== null && (int) $kelasTujuan->tahun_ajaran_id !== $tahunAjaranTujuanId && (int) $kelasTujuan->tahun_ajaran_id === $tahunAjaranAsalId && $kelasTujuan->id === $kelasAsal->id && $status === RiwayatKelas::STATUS_NAIK)) {
                $dilewati[] = "Kelas tujuan untuk {$siswa->nama} tidak valid pada tahun ajaran tujuan.";

                continue;
            }

            $tingkatAsal = $this->normalisasiTingkat($kelasAsal->tingkat);
            $tingkatTujuan = $this->normalisasiTingkat($kelasTujuan->tingkat);
            $tingkatDiharapkan = $status === RiwayatKelas::STATUS_NAIK
                ? $this->tingkatBerikutnya($tingkatAsal)
                : $tingkatAsal;

            // Untuk tingkat standar (9->10, 10->11, 11->12, 1->2, dst), validasi kesesuaian tingkat bila keduanya numerik standar
            if (
                $tingkatDiharapkan !== null
                && is_numeric($tingkatAsal)
                && is_numeric($tingkatTujuan)
                && $tingkatTujuan !== $tingkatDiharapkan
            ) {
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
                        'spp_id' => $siswa->spp_id,
                        'status' => $baris['status'],
                        'batch_id' => $batch->id,
                    ]
                );

                $tarifTujuan = Spp::untukTingkat($kelasTujuan->tingkat, $tahunAjaranTujuanNama) ?? $siswa->spp;

                RiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $kelasTujuan->id,
                    'tahun_ajaran_id' => $tahunAjaranTujuanId,
                    'spp_id' => $tarifTujuan?->id,
                    'status' => RiwayatKelas::STATUS_AKTIF,
                    'batch_id' => $batch->id,
                ]);

                $perubahanSiswa = ['kelas_id' => $kelasTujuan->id];

                if ($tarifTujuan) {
                    $perubahanSiswa['spp_id'] = $tarifTujuan->id;
                }

                $siswa->update($perubahanSiswa);
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

        if ($kelas->tahun_ajaran_id !== null && (int) $kelas->tahun_ajaran_id !== $tahunAjaranId) {
            $adaSiswa = $this->siswaKelas($kelasId, $tahunAjaranId)->isNotEmpty();

            if (! $adaSiswa) {
                throw new RuntimeException('Kelas tidak berada pada tahun ajaran yang dipilih.');
            }
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

            $tunggakan = $this->tunggakan($siswa);
            if (($tunggakan['nominal'] ?? 0) > 0) {
                $dilewati[] = "{$siswa->nama} masih memiliki tunggakan sebesar Rp " . number_format($tunggakan['nominal'], 0, ',', '.') . " dan tidak dapat diluluskan sebelum lunas.";

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
     * Outstanding monthly SPP arrears for a student across all assigned tariffs (historical + current) or for a specific academic year.
     *
     * @return array{jumlah_bulan: int, nominal: int, bulan: array<int, string>}
     */
    public function tunggakan(Siswa $siswa, ?int $tahunAjaranId = null): array
    {
        $siswa->loadMissing(['spp', 'riwayatKelas.spp', 'pembayarans']);
        $tanggunganService = app(\App\Support\Pembayaran\TanggunganPemerintahService::class);

        $tarifList = collect();

        if ($tahunAjaranId) {
            $riwayat = $siswa->riwayatKelas->firstWhere('tahun_ajaran_id', $tahunAjaranId);
            $tarifTahun = $riwayat?->spp;

            if (! $tarifTahun && $siswa->spp) {
                $taNama = TahunAjaran::query()->whereKey($tahunAjaranId)->value('nama');
                if ($siswa->spp->tahun_ajaran === $taNama) {
                    $tarifTahun = $siswa->spp;
                }
            }

            if ($tarifTahun) {
                $tarifList->push($tarifTahun);
            }
        } else {
            foreach ($siswa->riwayatKelas as $riwayat) {
                if ($riwayat->spp) {
                    $tarifList->push($riwayat->spp);
                }
            }

            if ($siswa->spp) {
                $tarifList->push($siswa->spp);
            }
        }

        $tarifList = $tarifList->unique('id')->values();

        if ($tarifList->isEmpty()) {
            return ['jumlah_bulan' => 0, 'nominal' => 0, 'bulan' => []];
        }

        $tunggakan = [];
        $totalTunggakan = 0;

        foreach ($tarifList as $tarif) {
            if (! $tarif->isBulanan()) {
                continue;
            }

            $nominal = (int) $tarif->nominal;
            $tahunMulai = Pembayaran::tahunMulaiTarif($tarif);

            $dibayarPerBulan = $siswa->pembayarans
                ->whereNull('void_at')
                ->where('spp_id', $tarif->id)
                ->whereNotNull('bulan_dibayar')
                ->groupBy(fn (Pembayaran $p): string => $p->bulan_dibayar.'|'.$p->tahun_dibayar)
                ->map(fn ($rows): int => (int) $rows->sum('jumlah_bayar'));

            foreach (Pembayaran::bulanTahunAjaran($tahunMulai) as $periode) {
                $bulan = $periode['bulan'];
                $tahun = $periode['tahun'];

                if ($tanggunganService->isSppDitanggung($siswa, $tarif, $bulan, $tahun)) {
                    continue;
                }

                $dibayar = (int) ($dibayarPerBulan->get($bulan.'|'.$tahun) ?? 0);
                $sisa = max(0, $nominal - $dibayar);

                if ($sisa > 0) {
                    $labelPeriode = "{$bulan} {$tahun}".($tarif->tahun_ajaran ? " ({$tarif->tahun_ajaran})" : '');
                    $tunggakan[] = $labelPeriode;
                    $totalTunggakan += $sisa;
                }
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
