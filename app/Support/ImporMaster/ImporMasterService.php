<?php

namespace App\Support\ImporMaster;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImporMasterService
{
    /**
     * Ensure only admin can execute master data import.
     *
     * @throws AuthorizationException
     */
    public function pastikanAdmin(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->isAdmin()) {
            throw new AuthorizationException('Fitur impor data master hanya dapat diakses oleh Administrator.');
        }
    }

    /**
     * Resolve file path from Livewire/Filament uploaded file payload.
     */
    public static function resolveUploadedFilePath(mixed $berkas): string
    {
        if (is_array($berkas)) {
            $berkas = reset($berkas);
        }

        if ($berkas instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
            return $berkas->getRealPath();
        }

        $raw = (string) $berkas;

        if (is_file($raw)) {
            return $raw;
        }

        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($raw)) {
            return \Illuminate\Support\Facades\Storage::disk('local')->path($raw);
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($raw)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->path($raw);
        }

        $candidate = storage_path('app/' . ltrim($raw, '/'));
        if (file_exists($candidate)) {
            return $candidate;
        }

        $candidatePublic = storage_path('app/public/' . ltrim($raw, '/'));
        if (file_exists($candidatePublic)) {
            return $candidatePublic;
        }

        return $raw;
    }

    /**
     * Parse spreadsheet into normalized array of associative rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function bacaSpreadsheet(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("Berkas spreadsheet tidak ditemukan: {$filePath}");
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $rawRows = [];

        if (in_array($ext, ['csv', 'txt'], true)) {
            $handle = fopen($filePath, 'r');
            if ($handle !== false) {
                while (($data = fgetcsv($handle, 0, ',')) !== false) {
                    if (count($data) === 1 && str_contains((string) $data[0], ';')) {
                        $data = str_getcsv((string) $data[0], ';');
                    }
                    $rawRows[] = $data;
                }
                fclose($handle);
            }
        } else {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray(null, true, true, false);
        }

        if (empty($rawRows)) {
            return [];
        }

        $rawHeaders = array_shift($rawRows);
        $headers = [];

        foreach ($rawHeaders as $idx => $h) {
            if ($h === null) {
                continue;
            }
            $clean = strtolower(trim((string) $h));
            $clean = str_replace([' ', '-', '.'], '_', $clean);
            $headers[$idx] = $clean;
        }

        $rows = [];
        foreach ($rawRows as $lineIndex => $row) {
            $nonEmpty = array_filter($row, fn ($val) => $val !== null && trim((string) $val) !== '');
            if (empty($nonEmpty)) {
                continue;
            }

            $item = ['_line' => $lineIndex + 2];
            foreach ($headers as $idx => $key) {
                $item[$key] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
            }
            $rows[] = $item;
        }

        return $rows;
    }

    /**
     * Import Jurusan (Kompetensi Keahlian) from file.
     *
     * @return array{berhasil: int, baru: int, diperbarui: int, gagal: int, errors: array<int, string>}
     */
    public function imporJurusan(string $filePath): array
    {
        $this->pastikanAdmin();

        $rows = $this->bacaSpreadsheet($filePath);
        $berhasil = 0;
        $baru = 0;
        $diperbarui = 0;
        $errors = [];

        foreach ($rows as $row) {
            $line = $row['_line'];
            $kode = strtoupper(trim((string) ($row['kode'] ?? $row['kode_jurusan'] ?? $row['singkatan'] ?? $row['kd_jurusan'] ?? '')));
            $nama = trim((string) ($row['nama'] ?? $row['nama_jurusan'] ?? $row['kompetensi_keahlian'] ?? $row['jurusan'] ?? ''));
            $deskripsi = trim((string) ($row['deskripsi'] ?? $row['keterangan'] ?? '')) ?: null;

            if ($kode === '' || $nama === '') {
                $errors[] = "Baris {$line}: Kolom 'kode' dan 'nama' wajib diisi.";
                continue;
            }

            $jurusan = Jurusan::whereRaw('UPPER(kode) = ?', [$kode])->first();

            if ($jurusan) {
                $jurusan->update([
                    'nama' => $nama,
                    'deskripsi' => $deskripsi ?? $jurusan->deskripsi,
                ]);
                $diperbarui++;
            } else {
                Jurusan::create([
                    'kode' => $kode,
                    'nama' => $nama,
                    'deskripsi' => $deskripsi,
                ]);
                $baru++;
            }

            $berhasil++;
        }

        if ($berhasil > 0) {
            LogAktivitas::catat(
                'jurusan.impor',
                "Mengimpor data jurusan: {$berhasil} diproses ({$baru} baru, {$diperbarui} diperbarui)",
                null,
                ['berhasil' => $berhasil, 'baru' => $baru, 'diperbarui' => $diperbarui, 'gagal' => count($errors)]
            );
        }

        return [
            'berhasil' => $berhasil,
            'baru' => $baru,
            'diperbarui' => $diperbarui,
            'gagal' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Import Data Kelas from file.
     *
     * @return array{berhasil: int, baru: int, diperbarui: int, gagal: int, errors: array<int, string>}
     */
    public function imporKelas(string $filePath, ?int $defaultTahunAjaranId = null): array
    {
        $this->pastikanAdmin();

        $rows = $this->bacaSpreadsheet($filePath);
        $berhasil = 0;
        $baru = 0;
        $diperbarui = 0;
        $errors = [];

        $activeTaId = $defaultTahunAjaranId
            ?? TahunAjaran::where('aktif', true)->value('id')
            ?? TahunAjaran::orderByDesc('nama')->value('id');

        $semuaJurusan = Jurusan::all();

        foreach ($rows as $row) {
            $line = $row['_line'];
            $namaKelas = trim((string) ($row['nama_kelas'] ?? $row['kelas'] ?? $row['nama'] ?? ''));
            $rawTingkat = trim((string) ($row['tingkat'] ?? $row['tingkat_kelas'] ?? $row['grade'] ?? ''));
            $rawJurusan = trim((string) ($row['kode_jurusan'] ?? $row['jurusan'] ?? $row['kode'] ?? ''));
            $rawTa = trim((string) ($row['tahun_ajaran'] ?? $row['ta'] ?? ''));
            $rawWali = trim((string) ($row['wali_kelas'] ?? $row['wali'] ?? $row['guru'] ?? ''));

            if ($namaKelas === '') {
                $errors[] = "Baris {$line}: Nama kelas wajib diisi.";
                continue;
            }

            // Normalisasi tingkat (10, 11, 12)
            $tingkat = Spp::normalisasiTingkat($rawTingkat);
            if (! $tingkat) {
                if (preg_match('/\b(12|XII)\b/i', $namaKelas)) {
                    $tingkat = '12';
                } elseif (preg_match('/\b(11|XI)\b/i', $namaKelas)) {
                    $tingkat = '11';
                } elseif (preg_match('/\b(10|X)\b/i', $namaKelas)) {
                    $tingkat = '10';
                } else {
                    $tingkat = '10'; // default
                }
            }

            // Temukan Jurusan
            $jurusan = null;
            if ($rawJurusan !== '') {
                $jurusan = $semuaJurusan->first(function ($j) use ($rawJurusan) {
                    return strcasecmp($j->kode, $rawJurusan) === 0 || strcasecmp($j->nama, $rawJurusan) === 0;
                });
            }

            if (! $jurusan) {
                // Cari dari string nama kelas
                foreach ($semuaJurusan as $j) {
                    if (stripos($namaKelas, $j->kode) !== false) {
                        $jurusan = $j;
                        break;
                    }
                }
            }

            if (! $jurusan) {
                // Jika belum ada jurusan, buatkan otomatis dari kode
                $kodeBaru = strtoupper($rawJurusan ?: preg_replace('/[^A-Za-z]/', '', $namaKelas));
                if ($kodeBaru !== '') {
                    $jurusan = Jurusan::firstOrCreate(
                        ['kode' => $kodeBaru],
                        ['nama' => $kodeBaru, 'deskripsi' => 'Dibuat otomatis dari impor kelas']
                    );
                    $semuaJurusan->push($jurusan);
                } else {
                    $errors[] = "Baris {$line}: Jurusan untuk kelas '{$namaKelas}' tidak ditemukan.";
                    continue;
                }
            }

            // Temukan Tahun Ajaran
            $tahunAjaranId = $activeTaId;
            if ($rawTa !== '') {
                $taModel = TahunAjaran::where('nama', $rawTa)->first();
                if ($taModel) {
                    $tahunAjaranId = $taModel->id;
                }
            }

            // Temukan Wali Kelas (opsional)
            $waliKelasId = null;
            if ($rawWali !== '') {
                $waliUser = User::where('username', $rawWali)
                    ->orWhere('name', 'like', "%{$rawWali}%")
                    ->orWhere('email', $rawWali)
                    ->first();
                if ($waliUser) {
                    $waliKelasId = $waliUser->id;
                }
            }

            $kelas = Kelas::where('nama_kelas', $namaKelas)
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->first();

            if ($kelas) {
                $kelas->update([
                    'tingkat' => $tingkat,
                    'jurusan_id' => $jurusan->id,
                    'wali_kelas_id' => $waliKelasId ?? $kelas->wali_kelas_id,
                ]);
                $diperbarui++;
            } else {
                Kelas::create([
                    'nama_kelas' => $namaKelas,
                    'tingkat' => $tingkat,
                    'jurusan_id' => $jurusan->id,
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'wali_kelas_id' => $waliKelasId,
                ]);
                $baru++;
            }

            $berhasil++;
        }

        if ($berhasil > 0) {
            LogAktivitas::catat(
                'kelas.impor',
                "Mengimpor data kelas: {$berhasil} diproses ({$baru} baru, {$diperbarui} diperbarui)",
                null,
                ['berhasil' => $berhasil, 'baru' => $baru, 'diperbarui' => $diperbarui, 'gagal' => count($errors)]
            );
        }

        return [
            'berhasil' => $berhasil,
            'baru' => $baru,
            'diperbarui' => $diperbarui,
            'gagal' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Import Master Tarif (SPP & Non-SPP) from file.
     *
     * @return array{berhasil: int, baru: int, diperbarui: int, gagal: int, errors: array<int, string>}
     */
    public function imporTarif(string $filePath): array
    {
        $this->pastikanAdmin();

        $rows = $this->bacaSpreadsheet($filePath);
        $berhasil = 0;
        $baru = 0;
        $diperbarui = 0;
        $errors = [];

        $activeTaNama = TahunAjaran::where('aktif', true)->value('nama')
            ?? TahunAjaran::orderByDesc('nama')->value('nama')
            ?? '2026/2027';

        foreach ($rows as $row) {
            $line = $row['_line'];
            $kategori = trim((string) ($row['kategori'] ?? $row['kategori_tarif'] ?? $row['jenis'] ?? ''));
            $nama = trim((string) ($row['nama'] ?? $row['nama_tarif'] ?? $row['keterangan_tarif'] ?? ''));
            $rawTipe = strtolower(trim((string) ($row['tipe'] ?? $row['jenis_bayar'] ?? '')));
            $tahunAjaran = trim((string) ($row['tahun_ajaran'] ?? $row['ta'] ?? '')) ?: $activeTaNama;
            $rawTingkat = trim((string) ($row['tingkat'] ?? $row['kelas'] ?? ''));
            $rawNominal = $row['nominal'] ?? $row['tarif'] ?? $row['biaya'] ?? $row['jumlah'] ?? 0;
            $keterangan = trim((string) ($row['keterangan'] ?? $row['catatan'] ?? '')) ?: null;

            if ($kategori === '') {
                $errors[] = "Baris {$line}: Kategori tarif wajib diisi.";
                continue;
            }

            if ($nama === '') {
                $nama = $kategori;
            }

            // Tentukan tipe
            $tipe = Spp::TIPE_SEKALI_BAYAR;
            if (str_contains($rawTipe, 'bulan') || (strcasecmp($kategori, 'SPP') === 0 && ! str_contains($rawTipe, 'sekali'))) {
                $tipe = Spp::TIPE_BULANAN;
            }

            // Tentukan tingkat: jika kosong, "-" atau "semua" -> null (berlaku semua tingkat)
            $tingkat = null;
            if ($rawTingkat !== '' && $rawTingkat !== '-' && stripos($rawTingkat, 'semua') === false) {
                $tingkat = Spp::normalisasiTingkat($rawTingkat);
            }

            // Bersihkan nominal
            $nominal = (int) preg_replace('/\D/', '', (string) $rawNominal);
            if ($nominal <= 0) {
                $errors[] = "Baris {$line}: Nominal tarif untuk '{$nama}' harus berupa angka lebih dari 0.";
                continue;
            }

            // Ambil 4 digit tahun dari tahun ajaran (mis. 2026 dari 2026/2027)
            $tahun = (int) now()->year;
            if (preg_match('/(\d{4})/', $tahunAjaran, $matches)) {
                $tahun = (int) $matches[1];
            }

            $spp = Spp::where('kategori', $kategori)
                ->where('nama', $nama)
                ->where('tahun_ajaran', $tahunAjaran)
                ->where('tingkat', $tingkat)
                ->first();

            if ($spp) {
                $spp->update([
                    'tipe' => $tipe,
                    'tahun' => $tahun,
                    'nominal' => $nominal,
                    'keterangan' => $keterangan ?? $spp->keterangan,
                ]);
                $diperbarui++;
            } else {
                Spp::create([
                    'kategori' => $kategori,
                    'nama' => $nama,
                    'tipe' => $tipe,
                    'tahun' => $tahun,
                    'tahun_ajaran' => $tahunAjaran,
                    'tingkat' => $tingkat,
                    'nominal' => $nominal,
                    'keterangan' => $keterangan,
                ]);
                $baru++;
            }

            $berhasil++;
        }

        if ($berhasil > 0) {
            LogAktivitas::catat(
                'spp.impor',
                "Mengimpor data tarif pembayaran: {$berhasil} diproses ({$baru} baru, {$diperbarui} diperbarui)",
                null,
                ['berhasil' => $berhasil, 'baru' => $baru, 'diperbarui' => $diperbarui, 'gagal' => count($errors)]
            );
        }

        return [
            'berhasil' => $berhasil,
            'baru' => $baru,
            'diperbarui' => $diperbarui,
            'gagal' => count($errors),
            'errors' => $errors,
        ];
    }
}
