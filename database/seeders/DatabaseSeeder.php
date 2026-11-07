<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\PembayaranGabunganBaris;
use App\Models\PengeluaranSekolah;
use App\Models\ProfilSekolah;
use App\Models\ProsesKelasBatch;
use App\Models\RiwayatKelas;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\SiswaTanggunganPemerintah;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database based on tarif_biaya_sekolah.xlsx.
     */
    public function run(): void
    {
        // 0. Hapus data demo lama
        $this->bersihkanDataLama();

        // 1. Pengguna Utama (Admin, Petugas, Wali Kelas)
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator SPP',
                'email' => 'admin@spp.test',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ]
        );

        $petugas = User::firstOrCreate(
            ['username' => 'petugas'],
            [
                'name' => 'Budi Santoso (Petugas SPP)',
                'email' => 'petugas@spp.test',
                'password' => Hash::make('password'),
                'role' => UserRole::Petugas,
            ]
        );

        $walikelas = User::firstOrCreate(
            ['username' => 'walikelas'],
            [
                'name' => 'Siti Aminah, S.Pd (Wali Kelas)',
                'email' => 'walikelas@spp.test',
                'password' => Hash::make('password'),
                'role' => UserRole::WaliKelas,
            ]
        );

        // 2. Hak Akses (Role Permissions)
        $this->seedRolePermissions();

        // 3. Profil Sekolah
        ProfilSekolah::current();

        // 4. Tahun Ajaran
        TahunAjaran::query()->update(['aktif' => false]);
        $daftarTA = [
            ['nama' => '2024/2025', 'tanggal_mulai' => '2024-07-01', 'tanggal_selesai' => '2025-06-30', 'aktif' => false],
            ['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-01', 'tanggal_selesai' => '2026-06-30', 'aktif' => false],
            ['nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30', 'aktif' => true],
        ];

        $taModels = [];
        foreach ($daftarTA as $ta) {
            $taModels[$ta['nama']] = TahunAjaran::updateOrCreate(
                ['nama' => $ta['nama']],
                $ta
            );
        }
        $taAktif = $taModels['2026/2027'];

        // 5. Jurusan
        $jurusans = [
            ['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak', 'deskripsi' => 'Pengembangan perangkat lunak, web, dan sistem cerdas.'],
            ['kode' => 'TKJ', 'nama' => 'Teknik Komputer dan Jaringan', 'deskripsi' => 'Infrastruktur jaringan komputer, sistem server, dan cloud.'],
            ['kode' => 'DKV', 'nama' => 'Desain Komunikasi Visual', 'deskripsi' => 'Desain grafis multimedia, ilustrasi, branding, dan periklanan.'],
            ['kode' => 'AKL', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'deskripsi' => 'Pengelolaan pembukuan keuangan lembaga dan perbankan.'],
        ];

        $jurusanModels = [];
        foreach ($jurusans as $item) {
            $jurusanModels[$item['kode']] = Jurusan::firstOrCreate(['kode' => $item['kode']], $item);
        }

        // 6. Rombel Kelas (Sesuai jumlah siswa di Excel: X 53 siswa, XI 56 siswa, XII 52 siswa)
        $kelasList = [
            ['nama_kelas' => 'X RPL 1', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['RPL']->id, 'wali_kelas_id' => $walikelas->id],
            ['nama_kelas' => 'X TKJ 1', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['TKJ']->id, 'wali_kelas_id' => null],
            ['nama_kelas' => 'XI RPL 1', 'tingkat' => '11', 'jurusan_id' => $jurusanModels['RPL']->id, 'wali_kelas_id' => null],
            ['nama_kelas' => 'XI TKJ 1', 'tingkat' => '11', 'jurusan_id' => $jurusanModels['TKJ']->id, 'wali_kelas_id' => null],
            ['nama_kelas' => 'XII RPL 1', 'tingkat' => '12', 'jurusan_id' => $jurusanModels['RPL']->id, 'wali_kelas_id' => null],
            ['nama_kelas' => 'XII TKJ 1', 'tingkat' => '12', 'jurusan_id' => $jurusanModels['TKJ']->id, 'wali_kelas_id' => null],
        ];

        $kelasModels = [];
        foreach ($kelasList as $kelasData) {
            $kelasModels[$kelasData['nama_kelas']] = Kelas::create(
                $kelasData + ['tahun_ajaran_id' => $taAktif->id]
            );
        }

        // 7. Tarif Biaya Sekolah Resmi (Sesuai Lampiran Excel tarif_biaya_sekolah.xlsx)
        $sppModels = $this->seedTarifExcel();

        // 8. Siswa & Profil Orang Tua (161 Siswa Realistis)
        // Kelas X: 53 siswa (27 X RPL 1, 26 X TKJ 1)
        // Kelas XI: 56 siswa (28 XI RPL 1, 28 XI TKJ 1)
        // Kelas XII: 52 siswa (26 XII RPL 1, 26 XII TKJ 1)
        $siswasXRPL = $this->buatDaftarSiswa(27, '10', $kelasModels['X RPL 1'], $sppModels['10']['SPP'], 10001001, 26001, 2026, $taModels, $kelasModels, $sppModels, true);
        $siswasXTKJ = $this->buatDaftarSiswa(26, '10', $kelasModels['X TKJ 1'], $sppModels['10']['SPP'], 10002001, 26028, 2026, $taModels, $kelasModels, $sppModels, false);

        $siswasXIRPL = $this->buatDaftarSiswa(28, '11', $kelasModels['XI RPL 1'], $sppModels['11']['SPP'], 11001001, 25001, 2025, $taModels, $kelasModels, $sppModels, false);
        $siswasXITKJ = $this->buatDaftarSiswa(28, '11', $kelasModels['XI TKJ 1'], $sppModels['11']['SPP'], 11002001, 25029, 2025, $taModels, $kelasModels, $sppModels, false);

        $siswasXIIRPL = $this->buatDaftarSiswa(26, '12', $kelasModels['XII RPL 1'], $sppModels['12']['SPP'], 12001001, 24001, 2024, $taModels, $kelasModels, $sppModels, false);
        $siswasXIITKJ = $this->buatDaftarSiswa(26, '12', $kelasModels['XII TKJ 1'], $sppModels['12']['SPP'], 12002001, 24027, 2024, $taModels, $kelasModels, $sppModels, false);

        // 9. Tanggungan Pemerintah / Beasiswa (Demo)
        SiswaTanggunganPemerintah::create([
            'siswa_id' => $siswasXRPL[5]->id,
            'spp_id' => $sppModels['10']['SPP']->id,
            'cakupan' => 'spp',
            'tanggal_mulai' => '2026-07-01',
            'keterangan' => 'Beasiswa Prestasi Kurang Mampu (Disdik Jabar)',
        ]);

        SiswaTanggunganPemerintah::create([
            'siswa_id' => $siswasXIRPL[10]->id,
            'spp_id' => $sppModels['11']['SPP']->id,
            'cakupan' => 'semua',
            'tanggal_mulai' => '2026-07-01',
            'keterangan' => 'Bantuan Penuh Program Indonesia Pintar (PIP)',
        ]);

        // 10. Transaksi Pembayaran Demo (SPP Bulanan & Pos Non-SPP)
        $this->seedPembayaranDemo(
            $petugas,
            $sppModels,
            $siswasXRPL,
            $siswasXTKJ,
            $siswasXIRPL,
            $siswasXITKJ,
            $siswasXIIRPL,
            $siswasXIITKJ
        );

        // 11. Pengeluaran Operasional Sekolah Demo
        $this->seedPengeluaranDemo($petugas, $taAktif);
    }

    /**
     * Membersihkan seluruh data demo lama.
     */
    private function bersihkanDataLama(): void
    {
        try {
            Schema::table('spps', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropUnique('spps_kategori_tahun_tingkat_unique');
            });
        } catch (\Throwable) {
            // Index might already be dropped or not exist
        }

        Schema::disableForeignKeyConstraints();

        PembayaranGabunganBaris::query()->truncate();
        PembayaranGabungan::query()->truncate();
        Pembayaran::query()->truncate();
        PengeluaranSekolah::query()->truncate();
        SiswaTanggunganPemerintah::query()->truncate();
        Wali::query()->truncate();
        RiwayatKelas::query()->truncate();
        ProsesKelasBatch::query()->truncate();
        Siswa::query()->truncate();
        Spp::query()->truncate();
        Kelas::query()->truncate();
        Jurusan::query()->truncate();
        User::where('role', UserRole::Siswa)->delete();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Menanamkan matriks izin peran (Role Permissions).
     */
    private function seedRolePermissions(): void
    {
        $permissions = [
            // Admin
            ['role' => 'admin', 'permission' => 'dashboard'],
            ['role' => 'admin', 'permission' => 'transaksi_spp'],
            ['role' => 'admin', 'permission' => 'laporan'],
            ['role' => 'admin', 'permission' => 'laporan_tunggakan'],
            ['role' => 'admin', 'permission' => 'portal_siswa'],
            ['role' => 'admin', 'permission' => 'kesiswaan'],
            ['role' => 'admin', 'permission' => 'master_data'],
            ['role' => 'admin', 'permission' => 'pengaturan_akses'],
            // Petugas
            ['role' => 'petugas', 'permission' => 'dashboard'],
            ['role' => 'petugas', 'permission' => 'transaksi_spp'],
            ['role' => 'petugas', 'permission' => 'laporan'],
            ['role' => 'petugas', 'permission' => 'laporan_tunggakan'],
            ['role' => 'petugas', 'permission' => 'portal_siswa'],
            ['role' => 'petugas', 'permission' => 'kesiswaan'],
            // Wali Kelas (View Only Laporan Tunggakan)
            ['role' => 'wali_kelas', 'permission' => 'dashboard'],
            ['role' => 'wali_kelas', 'permission' => 'laporan_tunggakan'],
            // Siswa
            ['role' => 'siswa', 'permission' => 'dashboard'],
            ['role' => 'siswa', 'permission' => 'portal_siswa'],
        ];

        foreach ($permissions as $p) {
            RolePermission::firstOrCreate([
                'role' => $p['role'],
                'permission' => $p['permission'],
            ]);
        }
    }

    /**
     * Memasukkan tarif biaya sekolah sesuai tarif_biaya_sekolah.xlsx.
     *
     * @return array<string, array<string, Spp>>
     */
    private function seedTarifExcel(): array
    {
        $ta = '2026/2027';
        $tahun = 2026;

        $kategoriMap = [
            'biaya sekolah per bulan' => ['SPP', 'bulanan'],
            'tabungan per bulan' => ['Tabungan', 'sekali_bayar'],
            'pts ganjil' => ['Biaya Ujian', 'sekali_bayar'],
            'pts genap' => ['Biaya Ujian', 'sekali_bayar'],
            'pas ganjil' => ['Biaya Ujian', 'sekali_bayar'],
            'pas genap' => ['Biaya Ujian', 'sekali_bayar'],
            'praktek' => ['Biaya Praktik', 'sekali_bayar'],
            'kegiatan sekolah' => ['Kegiatan Siswa', 'sekali_bayar'],
            'kalender' => ['Lain-lain', 'sekali_bayar'],
            'kaos olah raga' => ['Uang Seragam', 'sekali_bayar'],
            'katelpak / almamater' => ['Uang Seragam', 'sekali_bayar'],
            'map rapot' => ['Administrasi', 'sekali_bayar'],
            'study banding' => ['Kegiatan Siswa', 'sekali_bayar'],
            'anbk 1' => ['Biaya Ujian', 'sekali_bayar'],
            'anbk 2' => ['Biaya Ujian', 'sekali_bayar'],
            'prakerin' => ['Biaya Praktik', 'sekali_bayar'],
            'cicilan 1' => ['Uang Gedung', 'sekali_bayar'],
            'cicilan 2' => ['Uang Gedung', 'sekali_bayar'],
            'cicilan 3' => ['Uang Gedung', 'sekali_bayar'],
            'foto ijazah' => ['Kelulusan', 'sekali_bayar'],
            'bimker' => ['Bimbingan Karier', 'sekali_bayar'],
            'tasyakuran pelulusan' => ['Kelulusan', 'sekali_bayar'],
        ];

        // Definisi item per sheet Excel
        $daftarTarif = [
            '10' => [
                ['Biaya Sekolah per bulan', 100000, 'SPP', 'bulanan'],
                ['Tabungan per bulan', 35000, 'Tabungan', 'sekali_bayar'],
                ['PTS Ganjil', 250000, 'Biaya Ujian', 'sekali_bayar'],
                ['PTS Genap', 250000, 'Biaya Ujian', 'sekali_bayar'],
                ['PAS Ganjil', 300000, 'Biaya Ujian', 'sekali_bayar'],
                ['PAS Genap', 300000, 'Biaya Ujian', 'sekali_bayar'],
                ['Praktek', 300000, 'Biaya Praktik', 'sekali_bayar'],
                ['Kegiatan sekolah', 300000, 'Kegiatan Siswa', 'sekali_bayar'],
                ['Kalender', 30000, 'Lain-lain', 'sekali_bayar'],
                ['Kaos Olah Raga', 175000, 'Uang Seragam', 'sekali_bayar'],
                ['Katelpak / Almamater', 150000, 'Uang Seragam', 'sekali_bayar'],
                ['Map Rapot', 150000, 'Administrasi', 'sekali_bayar'],
                ['Study Banding', 300000, 'Kegiatan Siswa', 'sekali_bayar'],
            ],
            '11' => [
                ['Biaya Sekolah per bulan', 100000, 'SPP', 'bulanan'],
                ['Tabungan per bulan', 35000, 'Tabungan', 'sekali_bayar'],
                ['PTS Ganjil', 250000, 'Biaya Ujian', 'sekali_bayar'],
                ['PTS Genap', 250000, 'Biaya Ujian', 'sekali_bayar'],
                ['PAS Ganjil', 300000, 'Biaya Ujian', 'sekali_bayar'],
                ['PAS Genap', 300000, 'Biaya Ujian', 'sekali_bayar'],
                ['Praktek', 300000, 'Biaya Praktik', 'sekali_bayar'],
                ['Kegiatan sekolah', 400000, 'Kegiatan Siswa', 'sekali_bayar'],
                ['ANBK Gelombang 1', 100000, 'Biaya Ujian', 'sekali_bayar'],
                ['Kalender', 30000, 'Lain-lain', 'sekali_bayar'],
                ['Prakerin', 550000, 'Biaya Praktik', 'sekali_bayar'],
                ['ANBK Gelombang 2', 100000, 'Biaya Ujian', 'sekali_bayar'],
            ],
            '12' => [
                ['Biaya Sekolah per bulan', 100000, 'SPP', 'bulanan'],
                ['Tabungan per bulan', 35000, 'Tabungan', 'sekali_bayar'],
                ['PTS Ganjil', 250000, 'Biaya Ujian', 'sekali_bayar'],
                ['PTS Genap', 250000, 'Biaya Ujian', 'sekali_bayar'],
                ['PAS Ganjil', 300000, 'Biaya Ujian', 'sekali_bayar'],
                ['PAS Genap', 300000, 'Biaya Ujian', 'sekali_bayar'],
                ['Praktek', 300000, 'Biaya Praktik', 'sekali_bayar'],
                ['Kegiatan sekolah', 400000, 'Kegiatan Siswa', 'sekali_bayar'],
                ['Cicilan 1', 400000, 'Uang Gedung', 'sekali_bayar'],
                ['Cicilan 2', 400000, 'Uang Gedung', 'sekali_bayar'],
                ['Cicilan 3', 400000, 'Uang Gedung', 'sekali_bayar'],
                ['Foto Ijazah', 50000, 'Kelulusan', 'sekali_bayar'],
                ['Bimker', 150000, 'Bimbingan Karier', 'sekali_bayar'],
                ['Tasyakuran Pelulusan', 100000, 'Kelulusan', 'sekali_bayar'],
                ['Kalender', 30000, 'Lain-lain', 'sekali_bayar'],
            ],
        ];

        $models = [];
        $globalSpp = null;

        foreach ($daftarTarif as $tingkat => $items) {
            $models[$tingkat] = [];
            foreach ($items as $item) {
                [$nama, $nominal, $kategori, $tipe] = $item;

                if ($kategori === 'SPP') {
                    if (! $globalSpp) {
                        $globalSpp = Spp::create([
                            'kategori' => $kategori,
                            'tipe' => $tipe,
                            'nama' => $nama,
                            'tahun' => $tahun,
                            'tahun_ajaran' => $ta,
                            'tingkat' => null,
                            'nominal' => $nominal,
                            'keterangan' => "Tarif {$nama} (Berlaku untuk semua tingkat) TA {$ta}",
                        ]);
                    }
                    $models[$tingkat]['SPP'] = $globalSpp;
                    continue;
                }

                $spp = Spp::create([
                    'kategori' => $kategori,
                    'tipe' => $tipe,
                    'nama' => $nama,
                    'tahun' => $tahun,
                    'tahun_ajaran' => $ta,
                    'tingkat' => $tingkat,
                    'nominal' => $nominal,
                    'keterangan' => "Tarif {$nama} Tingkat {$tingkat} TA {$ta}",
                ]);

                $models[$tingkat][$nama] = $spp;
            }
        }

        return $models;
    }

    /**
     * Membuat sekelompok siswa dengan data realistis.
     *
     * @return array<int, Siswa>
     */
    private function buatDaftarSiswa(
        int $jumlah,
        string $tingkat,
        Kelas $kelas,
        Spp $sppBulanan,
        int $startNisn,
        int $startNis,
        int $tahunMasuk,
        array $taModels,
        array $kelasModels,
        array $sppModels,
        bool $buatAkunPortal = false
    ): array {
        $firstNamesL = ['Aditya', 'Bagas', 'Dimas', 'Fajar', 'Gilang', 'Hafiz', 'Irfan', 'Jaka', 'Kevin', 'Lukman', 'Maulana', 'Naufal', 'Pratama', 'Raditya', 'Rizky', 'Satria', 'Taufik', 'Wahyu', 'Yusuf', 'Zaki', 'Arif', 'Bayu', 'Candra', 'Danang', 'Eko', 'Farhan', 'Galih', 'Hendro', 'Iqbal', 'Kurniawan'];
        $firstNamesP = ['Annisa', 'Bella', 'Cantika', 'Dian', 'Eka', 'Fitri', 'Gita', 'Hesti', 'Indah', 'Jasmine', 'Kartika', 'Lestari', 'Maya', 'Nadia', 'Putri', 'Rani', 'Siti', 'Tiara', 'Ulfa', 'Wulan', 'Yulia', 'Zahra', 'Aulia', 'Citra', 'Dewi', 'Erna', 'Febri', 'Halimah', 'Intan', 'Kusuma'];
        $lastNames = ['Saputra', 'Pratama', 'Wibowo', 'Kusuma', 'Hidayat', 'Nugraha', 'Santoso', 'Wijaya', 'Permana', 'Setiawan', 'Utomo', 'Kurniawan', 'Ramadhan', 'Siregar', 'Nasution', 'Firmansyah', 'Gunawan', 'Mahendra', 'Purnama', 'Subagja'];

        $siswas = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $isMale = ($i % 2 === 0);
            $jk = $isMale ? 'L' : 'P';
            $fn = $isMale ? $firstNamesL[$i % count($firstNamesL)] : $firstNamesP[$i % count($firstNamesP)];
            $ln = $lastNames[($i * 3 + (int) $tingkat) % count($lastNames)];
            $nama = "{$fn} {$ln}";

            $nisn = (string) ($startNisn + $i);
            $nis = (string) ($startNis + $i);

            $userId = null;
            if ($buatAkunPortal && $i === 0) {
                $userSiswa = User::firstOrCreate(
                    ['username' => 'siswa'],
                    [
                        'name' => $nama,
                        'email' => 'siswa@spp.test',
                        'password' => Hash::make('password'),
                        'role' => UserRole::Siswa,
                    ]
                );
                $userId = $userSiswa->id;
            }

            $siswa = Siswa::withoutEvents(function () use (
                $userId, $kelas, $sppBulanan, $nisn, $nis, $nama, $jk, $i, $tingkat, $tahunMasuk
            ) {
                return Siswa::create([
                    'user_id' => $userId,
                    'kelas_id' => $kelas->id,
                    'spp_id' => $sppBulanan->id,
                    'nisn' => $nisn,
                    'nis' => $nis,
                    'nama' => $nama,
                    'jenis_kelamin' => $jk,
                    'alamat' => "Jl. Pemuda Mandiri No. ".($i + 1).", RT 0".(($i % 5) + 1)."/RW 0".(($i % 3) + 1).", Bandung",
                    'no_telp' => "0812{$tingkat}".str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'tahun_masuk' => $tahunMasuk,
                    'status' => 'aktif',
                    'is_archived' => false,
                ]);
            });

            Wali::updateOrCreate(
                ['siswa_id' => $siswa->id],
                [
                    'nama_ayah' => "Bapak {$fn}",
                    'nama_ibu' => "Ibu {$fn}",
                    'hp_wali' => "0813{$tingkat}".str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                ]
            );

            // Riwayat Kelas berdasarkan timeline multi-tahun
            if ($tingkat === '10') {
                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $taModels['2026/2027']->id,
                    ],
                    [
                        'kelas_id' => $kelas->id,
                        'spp_id' => $sppBulanan->id,
                        'status' => 'aktif',
                    ]
                );
            } elseif ($tingkat === '11') {
                $prevX = str_contains($kelas->nama_kelas, 'RPL') ? $kelasModels['X RPL 1'] : $kelasModels['X TKJ 1'];
                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $taModels['2025/2026']->id,
                    ],
                    [
                        'kelas_id' => $prevX->id,
                        'spp_id' => $sppModels['10']['SPP']->id,
                        'status' => 'naik',
                    ]
                );
                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $taModels['2026/2027']->id,
                    ],
                    [
                        'kelas_id' => $kelas->id,
                        'spp_id' => $sppBulanan->id,
                        'status' => 'aktif',
                    ]
                );
            } elseif ($tingkat === '12') {
                $prevX = str_contains($kelas->nama_kelas, 'RPL') ? $kelasModels['X RPL 1'] : $kelasModels['X TKJ 1'];
                $prevXI = str_contains($kelas->nama_kelas, 'RPL') ? $kelasModels['XI RPL 1'] : $kelasModels['XI TKJ 1'];
                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $taModels['2024/2025']->id,
                    ],
                    [
                        'kelas_id' => $prevX->id,
                        'spp_id' => $sppModels['10']['SPP']->id,
                        'status' => 'naik',
                    ]
                );
                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $taModels['2025/2026']->id,
                    ],
                    [
                        'kelas_id' => $prevXI->id,
                        'spp_id' => $sppModels['11']['SPP']->id,
                        'status' => 'naik',
                    ]
                );
                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $taModels['2026/2027']->id,
                    ],
                    [
                        'kelas_id' => $kelas->id,
                        'spp_id' => $sppBulanan->id,
                        'status' => 'aktif',
                    ]
                );
            }

            $siswas[] = $siswa;
        }

        return $siswas;
    }

    /**
     * Menanamkan transaksi pembayaran riil untuk demo.
     */
    private function seedPembayaranDemo(
        User $petugas,
        array $sppModels,
        array $siswasXRPL,
        array $siswasXTKJ,
        array $siswasXIRPL,
        array $siswasXITKJ,
        array $siswasXIIRPL,
        array $siswasXIITKJ
    ): void {
        $monthsTA = ['Juli', 'Agustus', 'September', 'Oktober', 'November'];
        $all12Months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
        $kodeUrutan = 1;

        // 1. Kelas XII: 12 siswa pertama dibuat 100% LUNAS sepanjang timeline 3 tahun
        // (Kelas X lunas, Kelas XI lunas, Kelas XII lunas)
        foreach (array_slice($siswasXIIRPL, 0, 12) as $siswa) {
            // Kelas X (2024/2025)
            foreach ($all12Months as $bulan) {
                $thn = in_array($bulan, ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true) ? 2024 : 2025;
                $kode = 'SPP-2024'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['10']['SPP']->id,
                    'tgl_bayar' => '2024-11-10',
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => $thn,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pembayaran SPP Bulan {$bulan} {$thn}",
                ]);
            }
            foreach ($sppModels['10'] as $key => $tarif) {
                if ($key === 'SPP') continue;
                $kode = 'NSP-2024'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $tarif->id,
                    'tgl_bayar' => '2024-09-15',
                    'bulan_dibayar' => null,
                    'tahun_dibayar' => 2024,
                    'jumlah_bayar' => $tarif->nominal,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pelunasan {$tarif->nama}",
                ]);
            }

            // Kelas XI (2025/2026)
            foreach ($all12Months as $bulan) {
                $thn = in_array($bulan, ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true) ? 2025 : 2026;
                $kode = 'SPP-2025'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['11']['SPP']->id,
                    'tgl_bayar' => '2025-11-10',
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => $thn,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pembayaran SPP Bulan {$bulan} {$thn}",
                ]);
            }
            foreach ($sppModels['11'] as $key => $tarif) {
                if ($key === 'SPP') continue;
                $kode = 'NSP-2025'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $tarif->id,
                    'tgl_bayar' => '2025-09-15',
                    'bulan_dibayar' => null,
                    'tahun_dibayar' => 2025,
                    'jumlah_bayar' => $tarif->nominal,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pelunasan {$tarif->nama}",
                ]);
            }

            // Kelas XII (2026/2027)
            foreach ($all12Months as $bulan) {
                $thn = in_array($bulan, ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true) ? 2026 : 2027;
                $kode = 'SPP-2026'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['12']['SPP']->id,
                    'tgl_bayar' => '2026-10-15',
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => $thn,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pembayaran SPP Bulan {$bulan} {$thn}",
                ]);
            }
            foreach ($sppModels['12'] as $key => $tarif) {
                if ($key === 'SPP') continue;
                $kode = 'NSP-2026'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $tarif->id,
                    'tgl_bayar' => '2026-10-20',
                    'bulan_dibayar' => null,
                    'tahun_dibayar' => 2026,
                    'jumlah_bayar' => $tarif->nominal,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pelunasan {$tarif->nama}",
                ]);
            }
        }

        // 2. Sisa siswa Kelas XII: variasi tunggakan multi-tahun
        $sisaXII = array_merge(array_slice($siswasXIIRPL, 12), $siswasXIITKJ);
        foreach ($sisaXII as $idx => $siswa) {
            // Grade 10: paid 10-12 months
            $paidX = 10 + ($idx % 3);
            for ($m = 0; $m < $paidX; $m++) {
                $bulan = $all12Months[$m];
                $thn = in_array($bulan, ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true) ? 2024 : 2025;
                $kode = 'SPP-2024'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['10']['SPP']->id,
                    'tgl_bayar' => '2024-11-10',
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => $thn,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pembayaran SPP Bulan {$bulan}",
                ]);
            }
            foreach ($sppModels['10'] as $key => $tarif) {
                if ($key === 'SPP' || ($idx % 2 === 1 && $key === 'Study Banding')) continue;
                $kode = 'NSP-2024'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $tarif->id,
                    'tgl_bayar' => '2024-09-15',
                    'bulan_dibayar' => null,
                    'tahun_dibayar' => 2024,
                    'jumlah_bayar' => $tarif->nominal,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pelunasan {$tarif->nama}",
                ]);
            }

            // Grade 11: paid 9-11 months
            $paidXI = 9 + ($idx % 3);
            for ($m = 0; $m < $paidXI; $m++) {
                $bulan = $all12Months[$m];
                $thn = in_array($bulan, ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true) ? 2025 : 2026;
                $kode = 'SPP-2025'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['11']['SPP']->id,
                    'tgl_bayar' => '2025-11-10',
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => $thn,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pembayaran SPP Bulan {$bulan}",
                ]);
            }

            // Grade 12 (running): paid 1-4 months
            $paidCount = ($idx % 4) + 1;
            for ($m = 0; $m < $paidCount; $m++) {
                $bulan = $monthsTA[$m];
                $kode = 'SPP-2026'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['12']['SPP']->id,
                    'tgl_bayar' => '2026-09-10',
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => 2026,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pembayaran SPP Bulan {$bulan}",
                ]);
            }
        }

        // 3. Siswa Kelas XI: Grade 10 completed + Grade 11 running
        $semuaXI = array_merge($siswasXIRPL, $siswasXITKJ);
        foreach ($semuaXI as $idx => $siswa) {
            if ($idx % 2 === 0) {
                // Grade 10 lunas
                foreach ($all12Months as $bulan) {
                    $thn = in_array($bulan, ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true) ? 2025 : 2026;
                    $kode = 'SPP-2025'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                    Pembayaran::create([
                        'kode_transaksi' => $kode,
                        'user_id' => $petugas->id,
                        'siswa_id' => $siswa->id,
                        'spp_id' => $sppModels['10']['SPP']->id,
                        'tgl_bayar' => '2025-10-15',
                        'bulan_dibayar' => $bulan,
                        'tahun_dibayar' => $thn,
                        'jumlah_bayar' => 100000,
                        'metode_pembayaran' => 'Tunai',
                        'catatan' => "Pembayaran SPP Bulan {$bulan}",
                    ]);
                }
                foreach ($sppModels['10'] as $key => $tarif) {
                    if ($key === 'SPP') continue;
                    $kode = 'NSP-2025'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                    Pembayaran::create([
                        'kode_transaksi' => $kode,
                        'user_id' => $petugas->id,
                        'siswa_id' => $siswa->id,
                        'spp_id' => $tarif->id,
                        'tgl_bayar' => '2025-08-20',
                        'bulan_dibayar' => null,
                        'tahun_dibayar' => 2025,
                        'jumlah_bayar' => $tarif->nominal,
                        'metode_pembayaran' => 'Tunai',
                        'catatan' => "Pelunasan {$tarif->nama}",
                    ]);
                }
            } else {
                // Grade 10 sebagian
                for ($m = 0; $m < 10; $m++) {
                    $bulan = $all12Months[$m];
                    $thn = in_array($bulan, ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], true) ? 2025 : 2026;
                    $kode = 'SPP-2025'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                    Pembayaran::create([
                        'kode_transaksi' => $kode,
                        'user_id' => $petugas->id,
                        'siswa_id' => $siswa->id,
                        'spp_id' => $sppModels['10']['SPP']->id,
                        'tgl_bayar' => '2025-10-15',
                        'bulan_dibayar' => $bulan,
                        'tahun_dibayar' => $thn,
                        'jumlah_bayar' => 100000,
                        'metode_pembayaran' => 'Tunai',
                        'catatan' => "Pembayaran SPP Bulan {$bulan}",
                    ]);
                }
            }

            // Grade 11 running
            $paidCount = (($idx + 1) % 5);
            for ($m = 0; $m < $paidCount; $m++) {
                $bulan = $monthsTA[$m];
                $kode = 'SPP-2026'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['11']['SPP']->id,
                    'tgl_bayar' => '2026-09-05',
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => 2026,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Transfer Bank',
                    'catatan' => "Pembayaran SPP Bulan {$bulan}",
                ]);
            }
        }

        // 4. Siswa Kelas X: Grade 10 running year
        $semuaX = array_merge($siswasXRPL, $siswasXTKJ);
        foreach ($semuaX as $idx => $siswa) {
            $paidCount = ($idx % 5);
            for ($m = 0; $m < $paidCount; $m++) {
                $bulan = $monthsTA[$m];
                $kode = 'SPP-2026'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['10']['SPP']->id,
                    'tgl_bayar' => '2026-08-15',
                    'bulan_dibayar' => $bulan,
                    'tahun_dibayar' => 2026,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pembayaran SPP Bulan {$bulan}",
                ]);
            }

            if ($idx % 3 === 0 && isset($sppModels['10']['Kaos Olah Raga'])) {
                $tarif = $sppModels['10']['Kaos Olah Raga'];
                $kode = 'NSP-2026'.str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $tarif->id,
                    'tgl_bayar' => '2026-08-20',
                    'bulan_dibayar' => null,
                    'tahun_dibayar' => 2026,
                    'jumlah_bayar' => $tarif->nominal,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => "Pembayaran {$tarif->nama}",
                ]);
            }
        }
    }

    /**
     * Menanamkan data pengeluaran operasional sekolah.
     */
    private function seedPengeluaranDemo(User $petugas, TahunAjaran $ta): void
    {
        $pengeluarans = [
            ['EXP-20261001-0001', '2026-10-01', 'Gaji & Honor Guru/Staf', 'Honor Guru Honorer dan Staf Kebersihan Bulan September', 4500000, 'Bendahara / Guru Honorer', 'Transfer Bank'],
            ['EXP-20261008-0002', '2026-10-08', 'ATK & Percetakan', 'Pengadaan Kertas HVS, Tinta Printer & Penggandaan Naskah PTS', 1250000, 'CV Media Prima Grafika', 'Tunai'],
            ['EXP-20261015-0003', '2026-10-15', 'Listrik, Air & Internet', 'Pembayaran Tagihan Listrik PLN & Internet Dedicated Fiber', 1850000, 'PT Telkom Indonesia & PLN', 'Transfer Bank'],
            ['EXP-20261022-0004', '2026-10-22', 'Sarana & Prasarana', 'Penggantian Switch Hub Gigabit & Kabel LAN Lab Komputer RPL', 950000, 'Toko Netkom Mandiri', 'Tunai'],
            ['EXP-20261028-0005', '2026-10-28', 'Pemeliharaan & Kebersihan', 'Service dan Cuci AC Ruang Guru dan Ruang Kasir TU', 450000, 'CV Sejuk Mandiri Teknik', 'Tunai'],
            ['EXP-20261102-0006', '2026-11-02', 'Konsumsi Rapat/Tamu', 'Konsumsi Rapat Koordinasi Wali Kelas & Sosialisasi Ujian', 350000, 'Catering Barokah', 'Tunai'],
        ];

        foreach ($pengeluarans as $p) {
            PengeluaranSekolah::create([
                'nomor_transaksi' => $p[0],
                'tanggal' => $p[1],
                'kategori' => $p[2],
                'keperluan' => $p[3],
                'nominal' => $p[4],
                'penerima' => $p[5],
                'metode_pembayaran' => $p[6],
                'user_id' => $petugas->id,
                'tahun_ajaran_id' => $ta->id,
            ]);
        }
    }
}
