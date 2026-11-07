<?php

namespace App\Support\ImporSiswa;

use App\Enums\StatusBarisImpor;
use App\Models\ImportSiswaStaging;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\Wali;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;
use Throwable;

class ImporSiswaService
{
    /**
     * @var array<int, array<string, Kelas>>
     */
    private array $cacheKelas = [];

    /**
     * Read the uploaded file into staging in 500-row chunks.
     */
    public function mulaiBatch(string $path, int $tahunAjaranId, int $sppId, ?int $userId, string $namaFile): string
    {
        $batchId = (string) Str::uuid();
        $import = new SiswaStagingImport($batchId, $tahunAjaranId, $sppId, $userId, basename($namaFile));

        try {
            Excel::import($import, $path);
        } catch (RuntimeException $exception) {
            $this->hapusBatch($batchId);

            throw $exception;
        } catch (Throwable $exception) {
            $this->hapusBatch($batchId);

            throw new RuntimeException(
                'Berkas Excel tidak dapat dibaca. Pastikan berkas berformat .xlsx dan tidak rusak.',
                0,
                $exception,
            );
        }

        if ($import->jumlahBaris() === 0) {
            $this->hapusBatch($batchId);

            throw new RuntimeException('Berkas tidak memuat baris data. Gunakan template impor yang disediakan.');
        }

        return $batchId;
    }

    /**
     * Validate every staged row and write the result back to staging.
     *
     * @return array<string, int>
     */
    public function validasiBatch(string $batchId, bool $perbarui = false): array
    {
        $batch = ImportSiswaStaging::query()->where('batch_id', $batchId)->firstOrFail();

        $jurusans = Jurusan::query()
            ->get()
            ->keyBy(fn (Jurusan $jurusan): string => PembacaNilai::normalisasiKode($jurusan->kode))
            ->all();

        $kelas = Kelas::query()
            ->where(function ($query) use ($batch): void {
                $query->where('tahun_ajaran_id', $batch->tahun_ajaran_id)
                    ->orWhereNull('tahun_ajaran_id');
            })
            ->get()
            ->keyBy(fn (Kelas $kelas): string => PembacaNilai::normalisasiKelas($kelas->nama_kelas))
            ->all();

        $terlihat = [
            'nisn' => [],
            'nik' => [],
            'nis' => [],
        ];

        ImportSiswaStaging::query()
            ->where('batch_id', $batchId)
            ->orderBy('baris')
            ->chunk(500, function ($barisBatch) use ($perbarui, $jurusans, $kelas, &$terlihat): void {
                $identitas = [];

                foreach ($barisBatch as $baris) {
                    $identitas[$baris->id] = [
                        'nisn' => $this->rapikanIdentitas($baris->nisn),
                        'nik' => $this->rapikanIdentitas($baris->nik),
                        'nis' => $this->rapikanIdentitas($baris->nis),
                    ];
                }

                [$byNisn, $byNik, $byNis] = $this->siswaYangSudahAda($identitas);

                foreach ($barisBatch as $baris) {
                    [$status, $alasan, $siswaId, $ditimpa] = $this->validasiBaris(
                        $baris,
                        $identitas[$baris->id],
                        $perbarui,
                        $jurusans,
                        $kelas,
                        $terlihat,
                        $byNisn,
                        $byNik,
                        $byNis,
                    );

                    $baris->update([
                        'status' => $status,
                        'alasan' => $alasan,
                        'siswa_id' => $siswaId,
                        'ditimpa' => $ditimpa,
                    ]);
                }
            });

        return $this->ringkasanBatch($batchId);
    }

    /**
     * Save valid rows chunk by chunk, each chunk in its own transaction.
     *
     * @return array<string, int>
     */
    public function simpanBatch(string $batchId, bool $perbarui = false): array
    {
        $contoh = ImportSiswaStaging::query()->where('batch_id', $batchId)->firstOrFail();
        $this->cacheKelas = [];

        $hasil = [
            'berhasil' => 0,
            'dilewati' => 0,
            'peringatan' => 0,
        ];

        ImportSiswaStaging::query()
            ->where('batch_id', $batchId)
            ->whereIn('status', [StatusBarisImpor::Valid->value, StatusBarisImpor::Peringatan->value])
            ->where('tersimpan', false)
            ->orderBy('id')
            ->chunkById(500, function ($barisBatch) use ($perbarui, &$hasil): void {
                DB::transaction(function () use ($barisBatch, $perbarui, &$hasil): void {
                    foreach ($barisBatch as $baris) {
                        $this->simpanBaris($baris, $perbarui, $hasil);
                    }
                });
            });

        $ringkasan = $this->ringkasanBatch($batchId);

        LogAktivitas::catat(
            'siswa.diimpor',
            "Impor siswa massal {$contoh->nama_file}: {$ringkasan['berhasil']} berhasil, {$ringkasan['dilewati']} dilewati",
            null,
            [
                'batch_id' => $batchId,
                'jumlah_baris' => $ringkasan['total'],
                'berhasil' => $ringkasan['berhasil'],
                'dilewati' => $ringkasan['dilewati'],
                'peringatan' => $ringkasan['peringatan_tersimpan'],
                'tahun_ajaran_id' => $contoh->tahun_ajaran_id,
                'perbarui_data' => $perbarui,
            ],
        );

        return $ringkasan;
    }

    /**
     * @return array<string, int>
     */
    public function ringkasanBatch(string $batchId): array
    {
        $baris = ImportSiswaStaging::query()
            ->where('batch_id', $batchId)
            ->get(['status', 'tersimpan', 'ditimpa']);

        $total = $baris->count();
        $error = $baris->where('status', StatusBarisImpor::Error)->count();
        $peringatan = $baris->where('status', StatusBarisImpor::Peringatan)->count();
        $valid = $baris->where('status', StatusBarisImpor::Valid)->count();
        $berhasil = $baris->where('tersimpan', true)->count();
        $peringatanTersimpan = $baris->where('status', StatusBarisImpor::Peringatan)->where('tersimpan', true)->count();

        return [
            'total' => $total,
            'valid' => $valid,
            'peringatan' => $peringatan,
            'error' => $error,
            'berhasil' => $berhasil,
            'dilewati' => $error,
            'peringatan_tersimpan' => $peringatanTersimpan,
            'akan_ditimpa' => $baris
                ->filter(fn (ImportSiswaStaging $item): bool => $item->ditimpa && $item->status !== StatusBarisImpor::Error)
                ->count(),
        ];
    }

    public function hapusBatch(string $batchId): void
    {
        ImportSiswaStaging::query()->where('batch_id', $batchId)->delete();
    }

    /**
     * @param  array<int, array{nisn: ?string, nik: ?string, nis: ?string}>  $identitas
     * @return array{0: array<string, Siswa>, 1: array<string, Siswa>, 2: array<string, Siswa>}
     */
    private function siswaYangSudahAda(array $identitas): array
    {
        $nisn = [];
        $nik = [];
        $nis = [];

        foreach ($identitas as $nilai) {
            if ($nilai['nisn'] !== null && preg_match('/^\d{10}$/', $nilai['nisn']) === 1) {
                $nisn[$nilai['nisn']] = true;
            }

            if ($nilai['nik'] !== null && preg_match('/^\d{16}$/', $nilai['nik']) === 1) {
                $nik[$nilai['nik']] = true;
            }

            if ($nilai['nis'] !== null) {
                $nis[$nilai['nis']] = true;
            }
        }

        $nisn = array_keys($nisn);
        $nik = array_keys($nik);
        $nis = array_keys($nis);

        if ($nisn === [] && $nik === [] && $nis === []) {
            return [[], [], []];
        }

        $siswa = Siswa::query()
            ->where(function ($query) use ($nisn, $nik, $nis): void {
                if ($nisn !== []) {
                    $query->orWhereIn('nisn', $nisn);
                }

                if ($nik !== []) {
                    $query->orWhereIn('nik', $nik);
                }

                if ($nis !== []) {
                    $query->orWhereIn('nis', $nis);
                }
            })
            ->get();

        $byNisn = [];
        $byNik = [];
        $byNis = [];

        foreach ($siswa as $item) {
            if ($item->nisn !== null) {
                $byNisn[(string) $item->nisn] = $item;
            }

            if ($item->nik !== null) {
                $byNik[(string) $item->nik] = $item;
            }

            if ($item->nis !== null) {
                $byNis[(string) $item->nis] = $item;
            }
        }

        return [$byNisn, $byNik, $byNis];
    }

    /**
     * @param  array{nisn: ?string, nik: ?string, nis: ?string}  $identitas
     * @param  array<string, Jurusan>  $jurusans
     * @param  array<string, Kelas>  $kelasMap
     * @param  array{nisn: array<string, int>, nik: array<string, int>, nis: array<string, int>}  $terlihat
     * @param  array<string, Siswa>  $byNisn
     * @param  array<string, Siswa>  $byNik
     * @param  array<string, Siswa>  $byNis
     * @return array{0: StatusBarisImpor, 1: ?string, 2: ?int, 3: bool}
     */
    private function validasiBaris(
        ImportSiswaStaging $baris,
        array $identitas,
        bool $perbarui,
        array $jurusans,
        array $kelasMap,
        array &$terlihat,
        array $byNisn,
        array $byNik,
        array $byNis,
    ): array {
        $error = [];
        $peringatan = [];
        $siswaId = null;
        $ditimpa = false;

        $nisn = $identitas['nisn'];
        $nik = $identitas['nik'];
        $nis = $identitas['nis'];

        if ($nis === null) {
            $error[] = 'NIS wajib diisi.';
        } elseif (isset($terlihat['nis'][$nis])) {
            $error[] = "NIS duplikat di berkas (baris {$terlihat['nis'][$nis]}).";
        } else {
            $terlihat['nis'][$nis] = $baris->baris;
        }

        if ($nisn === null) {
            $peringatan[] = 'Tanpa NISN.';
        } elseif (preg_match('/^\d{10}$/', $nisn) !== 1) {
            $error[] = 'NISN harus tepat 10 digit angka.';
        } elseif (isset($terlihat['nisn'][$nisn])) {
            $error[] = "NISN duplikat di berkas (baris {$terlihat['nisn'][$nisn]}).";
        } else {
            $terlihat['nisn'][$nisn] = $baris->baris;
        }

        if ($nik !== null) {
            if (preg_match('/^\d{16}$/', $nik) !== 1) {
                $error[] = 'NIK harus tepat 16 digit angka.';
            } elseif (isset($terlihat['nik'][$nik])) {
                $error[] = "NIK duplikat di berkas (baris {$terlihat['nik'][$nik]}).";
            } else {
                $terlihat['nik'][$nik] = $baris->baris;
            }
        }

        $wajib = [
            'nama_lengkap' => 'Nama lengkap',
            'jenis_kelamin' => 'Jenis kelamin',
            'tempat_lahir' => 'Tempat lahir',
            'tanggal_lahir' => 'Tanggal lahir',
            'tanggal_masuk' => 'Tanggal masuk',
            'kode_kompetensi' => 'Kode kompetensi',
            'kelas' => 'Kelas',
        ];

        foreach ($wajib as $kolom => $label) {
            if (blank($baris->{$kolom})) {
                $error[] = "{$label} wajib diisi.";
            }
        }

        $jenisKelamin = mb_strtoupper(trim((string) $baris->jenis_kelamin));

        if (! blank($baris->jenis_kelamin) && ! in_array($jenisKelamin, ['L', 'P'], true)) {
            $error[] = 'Jenis kelamin harus L atau P.';
        }

        $tanggalLahir = $this->tanggalValid($baris->tanggal_lahir);
        $tanggalMasuk = $this->tanggalValid($baris->tanggal_masuk);

        if (! blank($baris->tanggal_lahir) && $tanggalLahir === null) {
            $error[] = 'Tanggal lahir tidak valid.';
        } elseif ($tanggalLahir !== null && $tanggalLahir->isAfter(today())) {
            $error[] = 'Tanggal lahir tidak boleh di masa depan.';
        }

        if (! blank($baris->tanggal_masuk) && $tanggalMasuk === null) {
            $error[] = 'Tanggal masuk tidak valid.';
        }

        $jurusan = null;

        if (! blank($baris->kode_kompetensi)) {
            $kode = PembacaNilai::normalisasiKode($baris->kode_kompetensi);
            $jurusan = $jurusans[$kode] ?? null;

            if ($jurusan === null) {
                $error[] = "Kode kompetensi '{$baris->kode_kompetensi}' tidak ditemukan.";
            }
        }

        $kelas = null;

        if (! blank($baris->kelas)) {
            $namaKelas = PembacaNilai::normalisasiKelas($baris->kelas);
            $kelas = $kelasMap[$namaKelas] ?? null;

            if ($kelas === null) {
                $error[] = "Kelas '{$baris->kelas}' tidak terdaftar pada tahun ajaran tujuan.";
            } elseif ($jurusan !== null && (int) $kelas->jurusan_id !== (int) $jurusan->id) {
                $error[] = "Kelas '{$baris->kelas}' bukan kelas kompetensi {$jurusan->nama}.";
            }
        }

        if ($tanggalLahir !== null && $tanggalMasuk !== null && $kelas !== null) {
            $tingkat = $this->tingkatAngka($kelas->tingkat);

            if ($tingkat !== null) {
                $umur = (int) floor($tanggalLahir->diffInYears($tanggalMasuk));
                $patokan = 15 + ($tingkat - 10);
                $minimum = $patokan - 2;
                $maksimum = $patokan + 2;

                if ($umur < $minimum || $umur > $maksimum) {
                    $peringatan[] = "Umur {$umur} tahun di luar rentang wajar tingkat {$tingkat} ({$minimum}-{$maksimum} tahun).";
                }
            }
        }

        if (blank($baris->nama_ayah) && blank($baris->nama_ibu) && blank($baris->nama_wali)) {
            $error[] = 'Minimal satu dari nama ayah, ibu, atau wali wajib diisi.';
        }

        if (! blank($baris->hp_wali) && PembacaNilai::normalisasiHp($baris->hp_wali) === null) {
            $peringatan[] = 'Nomor HP wali tidak valid dan akan dikosongkan.';
        }

        $namaAneh = false;

        foreach ([$baris->nama_lengkap, $baris->nama_ayah, $baris->nama_ibu, $baris->nama_wali] as $nama) {
            if (PembacaNilai::mengandungSimbolAneh($nama)) {
                $namaAneh = true;
                break;
            }
        }

        if ($namaAneh) {
            $peringatan[] = 'Nama mengandung angka atau simbol tidak wajar.';
        }

        $temuan = [];

        if ($nisn !== null && isset($byNisn[$nisn])) {
            $temuan['NISN'] = $byNisn[$nisn];
        }

        if ($nik !== null && isset($byNik[$nik])) {
            $temuan['NIK'] = $byNik[$nik];
        }

        if ($nis !== null && isset($byNis[$nis])) {
            $temuan['NIS'] = $byNis[$nis];
        }

        if ($temuan !== []) {
            $idSiswa = collect($temuan)->pluck('id')->unique();

            if ($idSiswa->count() > 1) {
                $error[] = 'NISN/NIK/NIS cocok ke siswa yang berbeda. Periksa kembali data.';
            } else {
                /** @var Siswa $siswa */
                $siswa = reset($temuan);

                if ($perbarui) {
                    $siswaId = (int) $siswa->id;
                    $ditimpa = true;
                } else {
                    foreach ($temuan as $label => $item) {
                        $error[] = "{$label} sudah terdaftar (siswa {$item->nama}).";
                    }
                }
            }
        }

        $alasan = implode("\n", array_merge($error, $peringatan));

        $status = match (true) {
            $error !== [] => StatusBarisImpor::Error,
            $peringatan !== [] => StatusBarisImpor::Peringatan,
            default => StatusBarisImpor::Valid,
        };

        return [$status, $alasan === '' ? null : $alasan, $siswaId, $ditimpa];
    }

    /**
     * @param  array{berhasil: int, dilewati: int, peringatan: int}  $hasil
     */
    private function simpanBaris(ImportSiswaStaging $baris, bool $perbarui, array &$hasil): void
    {
        $kelas = $this->kelasUntukBaris($baris);

        if ($kelas === null) {
            $this->tandaiDilewati($baris, 'Kelas tidak ditemukan saat proses simpan.');
            $hasil['dilewati']++;

            return;
        }

        $siswa = $this->cariSiswa($baris);

        if ($siswa !== null && ! $perbarui) {
            $this->tandaiDilewati($baris, 'Siswa sudah terdaftar saat proses simpan (baris dilewati).');
            $hasil['dilewati']++;

            return;
        }

        $hp = PembacaNilai::normalisasiHp($baris->hp_wali);
        $namaTahunAjaran = $kelas->tahunAjaran?->nama
            ?? \App\Models\TahunAjaran::query()->whereKey($baris->tahun_ajaran_id)->value('nama');
        $spp = Spp::untukTingkat($kelas->tingkat, $namaTahunAjaran);
        $sppId = $spp?->id ?? $baris->spp_id;

        $tahunMasuk = filled($baris->tanggal_masuk) && preg_match('/^(\d{4})/', (string) $baris->tanggal_masuk, $m)
            ? (int) $m[1]
            : null;

        $data = [
            'kelas_id' => $kelas->id,
            'spp_id' => $sppId,
            'nisn' => $baris->nisn,
            'nik' => $baris->nik,
            'nis' => $baris->nis,
            'nama' => $baris->nama_lengkap,
            'jenis_kelamin' => mb_strtoupper((string) $baris->jenis_kelamin),
            'tempat_lahir' => $baris->tempat_lahir,
            'tanggal_lahir' => $baris->tanggal_lahir,
            'agama' => $baris->agama,
            'alamat' => $baris->alamat,
            'tanggal_masuk' => $baris->tanggal_masuk,
            'tahun_masuk' => $tahunMasuk,
            'no_telp' => $hp,
        ];

        if ($siswa === null) {
            $siswa = Siswa::query()->create($data + [
                'status' => 'aktif',
            ]);
        } else {
            $siswa->update($data);
        }

        Wali::query()->updateOrCreate(
            ['siswa_id' => $siswa->id],
            [
                'nama_ayah' => $baris->nama_ayah,
                'nama_ibu' => $baris->nama_ibu,
                'nama_wali' => $baris->nama_wali,
                'hp_wali' => $hp,
            ],
        );

        RiwayatKelas::query()->updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'tahun_ajaran_id' => $baris->tahun_ajaran_id,
            ],
            [
                'kelas_id' => $kelas->id,
                'spp_id' => $sppId,
            ],
        );

        $baris->update([
            'siswa_id' => $siswa->id,
            'tersimpan' => true,
        ]);

        $hasil['berhasil']++;

        if ($baris->status === StatusBarisImpor::Peringatan) {
            $hasil['peringatan']++;
        }
    }

    private function tandaiDilewati(ImportSiswaStaging $baris, string $alasan): void
    {
        $baris->update([
            'status' => StatusBarisImpor::Error,
            'alasan' => trim(($baris->alasan ? $baris->alasan."\n" : '').$alasan),
            'tersimpan' => false,
        ]);
    }

    private function kelasUntukBaris(ImportSiswaStaging $baris): ?Kelas
    {
        $tahunAjaranId = (int) $baris->tahun_ajaran_id;

        if (! isset($this->cacheKelas[$tahunAjaranId])) {
            $this->cacheKelas[$tahunAjaranId] = Kelas::query()
                ->with('tahunAjaran')
                ->where(function ($query) use ($tahunAjaranId): void {
                    $query->where('tahun_ajaran_id', $tahunAjaranId)
                        ->orWhereNull('tahun_ajaran_id');
                })
                ->get()
                ->keyBy(fn (Kelas $kelas): string => PembacaNilai::normalisasiKelas($kelas->nama_kelas))
                ->all();
        }

        return $this->cacheKelas[$tahunAjaranId][PembacaNilai::normalisasiKelas($baris->kelas)] ?? null;
    }

    private function cariSiswa(ImportSiswaStaging $baris): ?Siswa
    {
        $kunci = [];

        if ($baris->nisn !== null) {
            $kunci[] = ['nisn', $baris->nisn];
        }

        if ($baris->nik !== null) {
            $kunci[] = ['nik', $baris->nik];
        }

        if ($baris->nis !== null) {
            $kunci[] = ['nis', $baris->nis];
        }

        if ($kunci === []) {
            return null;
        }

        return Siswa::query()
            ->where(function ($query) use ($kunci): void {
                foreach ($kunci as [$kolom, $nilai]) {
                    $query->orWhere($kolom, $nilai);
                }
            })
            ->first();
    }

    private function rapikanIdentitas(?string $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = preg_replace('/\s+/u', '', $nilai);

        return $nilai === '' ? null : $nilai;
    }

    private function tanggalValid(?string $nilai): ?Carbon
    {
        if ($nilai === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) !== 1) {
            return null;
        }

        try {
            $tanggal = Carbon::createFromFormat('!Y-m-d', $nilai);

            return $tanggal->format('Y-m-d') === $nilai ? $tanggal : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function tingkatAngka(?string $tingkat): ?int
    {
        $normal = Spp::normalisasiTingkat($tingkat);

        return $normal !== null && is_numeric($normal) ? (int) $normal : null;
    }
}

