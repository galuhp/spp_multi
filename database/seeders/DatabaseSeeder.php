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

        // 6. Rombel Kelas (2 Kelas Utama X RPL 1 & X TKJ 1, serta 2 Kelas Tujuan Promosi XI RPL 1 & XI TKJ 1)
        $kelasList = [
            ['nama_kelas' => 'X RPL 1', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['RPL']->id, 'wali_kelas_id' => $walikelas->id],
            ['nama_kelas' => 'X TKJ 1', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['TKJ']->id, 'wali_kelas_id' => null],
            ['nama_kelas' => 'XI RPL 1', 'tingkat' => '11', 'jurusan_id' => $jurusanModels['RPL']->id, 'wali_kelas_id' => null],
            ['nama_kelas' => 'XI TKJ 1', 'tingkat' => '11', 'jurusan_id' => $jurusanModels['TKJ']->id, 'wali_kelas_id' => null],
        ];

        $kelasModels = [];
        foreach ($kelasList as $kelasData) {
            $kelasModels[$kelasData['nama_kelas']] = Kelas::create(
                $kelasData + ['tahun_ajaran_id' => $taAktif->id]
            );
        }

        // 7. Tarif Biaya Sekolah Resmi (Sesuai Lampiran Excel tarif_biaya_sekolah.xlsx)
        $sppModels = $this->seedTarifExcel();

        // 8. Siswa & Profil Orang Tua (2 Kelas: X RPL 1 & X TKJ 1, 10 Siswa per Kelas, Seluruh Nama Unik 100%)
        $siswaDataXRPL = [
            ['Aditya Pratama', 'L', 10001001, 26001, true], // Akun portal siswa: username 'siswa', password 'password'
            ['Annisa Larasati', 'P', 10001002, 26002, false],
            ['Bagas Wicaksono', 'L', 10001003, 26003, false],
            ['Bella Permata', 'P', 10001004, 26004, false],
            ['Candra Gunawan', 'L', 10001005, 26005, false],
            ['Cantika Maharani', 'P', 10001006, 26006, false], // Beasiswa
            ['Dimas Setiawan', 'L', 10001007, 26007, false],
            ['Dian Anggraini', 'P', 10001008, 26008, false],
            ['Eko Prasetyo', 'L', 10001009, 26009, false],
            ['Eka Rahmawati', 'P', 10001010, 26010, false],
        ];

        $siswaDataXTKJ = [
            ['Farhan Hidayat', 'L', 10002001, 26021, false],
            ['Fitri Handayani', 'P', 10002002, 26022, false],
            ['Gilang Ramadhan', 'L', 10002003, 26023, false],
            ['Gita Nirmala', 'P', 10002004, 26024, false],
            ['Hafiz Nugroho', 'L', 10002005, 26025, false],
            ['Hesti Wulandari', 'P', 10002006, 26026, false],
            ['Irfan Kurniawan', 'L', 10002007, 26027, false],
            ['Indah Puspitasari', 'P', 10002008, 26028, false],
            ['Jaka Santoso', 'L', 10002009, 26029, false],
            ['Jasmine Zahra', 'P', 10002010, 26030, false],
        ];

        $siswasXRPL = $this->buatDaftarSiswa($siswaDataXRPL, '10', $kelasModels['X RPL 1'], $sppModels['10']['SPP'], 2026, $taModels);
        $siswasXTKJ = $this->buatDaftarSiswa($siswaDataXTKJ, '10', $kelasModels['X TKJ 1'], $sppModels['10']['SPP'], 2026, $taModels);

        // 9. Tanggungan Pemerintah / Beasiswa (Demo)
        SiswaTanggunganPemerintah::create([
            'siswa_id' => $siswasXRPL[5]->id, // Cantika Maharani
            'spp_id' => null,
            'cakupan' => SiswaTanggunganPemerintah::CAKUPAN_SPP,
            'mulai' => '2026-07-01',
            'alasan' => 'Beasiswa Prestasi Kurang Mampu (Disdik Jabar)',
            'created_by' => $petugas->id,
        ]);

        // 10. Transaksi Pembayaran Demo (SPP Bulanan & Pos Non-SPP)
        $this->seedPembayaranDemo(
            $petugas,
            $sppModels,
            $siswasXRPL,
            $siswasXTKJ
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
     * Membuat sekelompok siswa dengan data eksplisit dan nama 100% unik.
     *
     * @return array<int, Siswa>
     */
    private function buatDaftarSiswa(
        array $daftarSiswaData,
        string $tingkat,
        Kelas $kelas,
        Spp $sppBulanan,
        int $tahunMasuk,
        array $taModels
    ): array {
        $siswas = [];

        foreach ($daftarSiswaData as $item) {
            [$nama, $jk, $nisn, $nis, $buatAkunPortal] = $item;

            $userId = null;
            if ($buatAkunPortal) {
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
                $userId, $kelas, $sppBulanan, $nisn, $nis, $nama, $jk, $tingkat, $tahunMasuk
            ) {
                return Siswa::create([
                    'user_id' => $userId,
                    'kelas_id' => $kelas->id,
                    'spp_id' => $sppBulanan->id,
                    'nisn' => (string) $nisn,
                    'nis' => (string) $nis,
                    'nama' => $nama,
                    'jenis_kelamin' => $jk,
                    'alamat' => "Jl. Pemuda Mandiri No. " . substr((string) $nis, -2) . ", Bandung",
                    'no_telp' => "0812{$tingkat}" . substr((string) $nisn, -4),
                    'tahun_masuk' => $tahunMasuk,
                    'status' => 'aktif',
                    'is_archived' => false,
                ]);
            });

            Wali::updateOrCreate(
                ['siswa_id' => $siswa->id],
                [
                    'nama_ayah' => "Bapak " . explode(' ', $nama)[0],
                    'nama_ibu' => "Ibu " . explode(' ', $nama)[0],
                    'hp_wali' => "0813{$tingkat}" . substr((string) $nisn, -4),
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

            $siswas[] = $siswa;
        }

        return $siswas;
    }

    /**
     * Menanamkan transaksi pembayaran riil untuk demo (SPP dan Pos Non-SPP).
     */
    private function seedPembayaranDemo(
        User $petugas,
        array $sppModels,
        array $siswasXRPL,
        array $siswasXTKJ
    ): void {
        $monthsTA = ['Juli', 'Agustus', 'September', 'Oktober', 'November'];
        $kodeUrutan = 1;
        $semuaSiswa = array_merge($siswasXRPL, $siswasXTKJ);
        $nonSppX = $sppModels['10'] ?? [];

        foreach ($semuaSiswa as $idx => $siswa) {
            $mod = $idx % 10;

            if ($mod === 0 || $mod === 1) {
                // Pola 1: Lunas 100% SPP (Juli - November) + Lunas seluruh pos Non-SPP
                foreach ($monthsTA as $bulan) {
                    $kode = 'SPP-2026' . str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                    Pembayaran::create([
                        'kode_transaksi' => $kode,
                        'user_id' => $petugas->id,
                        'siswa_id' => $siswa->id,
                        'spp_id' => $sppModels['10']['SPP']->id,
                        'tgl_bayar' => '2026-10-15',
                        'bulan_dibayar' => $bulan,
                        'tahun_dibayar' => 2026,
                        'jumlah_bayar' => 100000,
                        'metode_pembayaran' => 'Tunai',
                        'catatan' => "Pembayaran SPP Bulan {$bulan}",
                    ]);
                }
                foreach ($nonSppX as $key => $tarif) {
                    if ($key === 'SPP') continue;
                    $kode = 'NSP-2026' . str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                    Pembayaran::create([
                        'kode_transaksi' => $kode,
                        'user_id' => $petugas->id,
                        'siswa_id' => $siswa->id,
                        'spp_id' => $tarif->id,
                        'tgl_bayar' => '2026-09-10',
                        'bulan_dibayar' => null,
                        'tahun_dibayar' => 2026,
                        'jumlah_bayar' => $tarif->nominal,
                        'metode_pembayaran' => 'Tunai',
                        'catatan' => "Pelunasan {$tarif->nama}",
                    ]);
                }
            } elseif ($mod === 2 || $mod === 3) {
                // Pola 2: Bayar SPP 3 bulan (Juli, Agustus, September) + Sebagian Non-SPP
                foreach (array_slice($monthsTA, 0, 3) as $bulan) {
                    $kode = 'SPP-2026' . str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                    Pembayaran::create([
                        'kode_transaksi' => $kode,
                        'user_id' => $petugas->id,
                        'siswa_id' => $siswa->id,
                        'spp_id' => $sppModels['10']['SPP']->id,
                        'tgl_bayar' => '2026-09-15',
                        'bulan_dibayar' => $bulan,
                        'tahun_dibayar' => 2026,
                        'jumlah_bayar' => 100000,
                        'metode_pembayaran' => 'Tunai',
                        'catatan' => "Pembayaran SPP Bulan {$bulan}",
                    ]);
                }
                foreach (['Kaos Olah Raga', 'Katelpak / Almamater', 'Map Rapot'] as $namaTarif) {
                    if (isset($nonSppX[$namaTarif])) {
                        $tarif = $nonSppX[$namaTarif];
                        $kode = 'NSP-2026' . str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
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
            } elseif ($mod === 4) {
                // Pola 3: Bayar SPP 1 bulan (Juli)
                $kode = 'SPP-2026' . str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                Pembayaran::create([
                    'kode_transaksi' => $kode,
                    'user_id' => $petugas->id,
                    'siswa_id' => $siswa->id,
                    'spp_id' => $sppModels['10']['SPP']->id,
                    'tgl_bayar' => '2026-07-20',
                    'bulan_dibayar' => 'Juli',
                    'tahun_dibayar' => 2026,
                    'jumlah_bayar' => 100000,
                    'metode_pembayaran' => 'Tunai',
                    'catatan' => 'Pembayaran SPP Bulan Juli',
                ]);
            } elseif ($mod === 6 || $mod === 7) {
                // Pola 4: Bayar SPP 2 bulan (Juli, Agustus) via Transfer Bank
                foreach (array_slice($monthsTA, 0, 2) as $bulan) {
                    $kode = 'SPP-2026' . str_pad((string) $kodeUrutan++, 5, '0', STR_PAD_LEFT);
                    Pembayaran::create([
                        'kode_transaksi' => $kode,
                        'user_id' => $petugas->id,
                        'siswa_id' => $siswa->id,
                        'spp_id' => $sppModels['10']['SPP']->id,
                        'tgl_bayar' => '2026-08-15',
                        'bulan_dibayar' => $bulan,
                        'tahun_dibayar' => 2026,
                        'jumlah_bayar' => 100000,
                        'metode_pembayaran' => 'Transfer Bank',
                        'catatan' => "Pembayaran SPP Bulan {$bulan}",
                    ]);
                }
            }
            // Mod 5 (Cantika, beasiswa) & Mod 8, 9 (belum bayar) tetap tanpa pembayaran untuk demo tunggakan
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
