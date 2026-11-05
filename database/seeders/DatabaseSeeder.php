<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Users (Admin & Petugas)
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

        // 2. Jurusan
        $jurusans = [
            ['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak', 'deskripsi' => 'Pengembangan perangkat lunak, web, dan aplikasi mobile.'],
            ['kode' => 'TKJ', 'nama' => 'Teknik Komputer dan Jaringan', 'deskripsi' => 'Instalasi jaringan, server, dan infrastruktur IT.'],
            ['kode' => 'DKV', 'nama' => 'Desain Komunikasi Visual', 'deskripsi' => 'Desain grafis, multimedia, video editing, dan animasi.'],
            ['kode' => 'AKL', 'nama' => 'Akuntansi dan Keuangan Lembaga', 'deskripsi' => 'Pengelolaan pembukuan, perbankan, dan laporan keuangan.'],
        ];

        $jurusanModels = [];
        foreach ($jurusans as $item) {
            $jurusanModels[$item['kode']] = Jurusan::firstOrCreate(['kode' => $item['kode']], $item);
        }

        // 3. Kelas
        $kelasList = [
            ['nama_kelas' => 'X RPL 1', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['RPL']->id],
            ['nama_kelas' => 'X RPL 2', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['RPL']->id],
            ['nama_kelas' => 'XI RPL 1', 'tingkat' => '11', 'jurusan_id' => $jurusanModels['RPL']->id],
            ['nama_kelas' => 'XII RPL 1', 'tingkat' => '12', 'jurusan_id' => $jurusanModels['RPL']->id],
            ['nama_kelas' => 'X TKJ 1', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['TKJ']->id],
            ['nama_kelas' => 'XI TKJ 1', 'tingkat' => '11', 'jurusan_id' => $jurusanModels['TKJ']->id],
            ['nama_kelas' => 'X DKV 1', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['DKV']->id],
            ['nama_kelas' => 'X AKL 1', 'tingkat' => '10', 'jurusan_id' => $jurusanModels['AKL']->id],
        ];

        $kelasModels = [];
        foreach ($kelasList as $kelas) {
            $kelasModels[$kelas['nama_kelas']] = Kelas::firstOrCreate(
                ['nama_kelas' => $kelas['nama_kelas']],
                $kelas
            );
        }

        // 4. Tahun Ajaran
        TahunAjaran::query()->update(['aktif' => false]);

        $tahunAjaranModels = [];
        $daftarTahunAjaran = [
            ['nama' => '2024/2025', 'tanggal_mulai' => '2024-07-01', 'tanggal_selesai' => '2025-06-30'],
            ['nama' => '2025/2026', 'tanggal_mulai' => '2025-07-01', 'tanggal_selesai' => '2026-06-30'],
            ['nama' => '2026/2027', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2027-06-30'],
        ];

        foreach ($daftarTahunAjaran as $tahunAjaran) {
            $tahunAjaranModels[$tahunAjaran['nama']] = TahunAjaran::updateOrCreate(
                ['nama' => $tahunAjaran['nama']],
                $tahunAjaran + ['aktif' => $tahunAjaran['nama'] === '2026/2027'],
            );
        }

        foreach ($kelasModels as $kelas) {
            $kelas->update(['tahun_ajaran_id' => $tahunAjaranModels['2026/2027']->id]);
        }

        // 5. SPP & Fee Tariffs
        $this->buatTarifSpp(2025, '2024/2025', 200000, 'Tarif SPP TA 2024/2025');
        $this->buatTarifSpp(2026, '2025/2026', 225000, 'Tarif SPP TA 2025/2026');
        $spp2027 = $this->buatTarifSpp(2027, '2026/2027', 250000, 'Tarif SPP TA 2026/2027 (Berjalan)');

        $gedung2027 = Spp::firstOrCreate(
            ['tahun' => 2027, 'kategori' => Spp::KATEGORI_UANG_GEDUNG],
            [
                'kategori' => Spp::KATEGORI_UANG_GEDUNG,
                'tipe' => Spp::TIPE_SEKALI_BAYAR,
                'nama' => 'Uang Gedung & Pengembangan Sarana',
                'tahun_ajaran' => '2026/2027',
                'nominal' => 2500000,
                'keterangan' => 'Sumbangan fasilitas dan gedung siswa baru angkatan 2026/2027',
            ]
        );

        $seragam2027 = Spp::firstOrCreate(
            ['tahun' => 2027, 'kategori' => Spp::KATEGORI_UANG_SERAGAM],
            [
                'kategori' => Spp::KATEGORI_UANG_SERAGAM,
                'tipe' => Spp::TIPE_SEKALI_BAYAR,
                'nama' => 'Paket Seragam Sekolah Lengkap (4 Stel)',
                'tahun_ajaran' => '2026/2027',
                'nominal' => 850000,
                'keterangan' => 'Seragam OSIS, Pramuka, Kejuruan, dan Olahraga',
            ]
        );

        // 5. User Siswa
        $userSiswa1 = User::firstOrCreate(
            ['username' => '0071234561'],
            [
                'name' => 'Ahmad Rifai',
                'email' => 'ahmad@siswa.spp.test',
                'password' => Hash::make('password'),
                'role' => UserRole::Siswa,
            ]
        );

        $userSiswa2 = User::firstOrCreate(
            ['username' => 'siswa'], // shortcut siswa login
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@siswa.spp.test',
                'password' => Hash::make('password'),
                'role' => UserRole::Siswa,
            ]
        );

        // 6. Siswa Records
        $siswaData = [
            [
                'user_id' => $userSiswa1->id,
                'kelas_id' => $kelasModels['X RPL 1']->id,
                'spp_id' => $spp2027['10']->id,
                'nisn' => '0071234561',
                'nis' => '2026001',
                'nama' => 'Ahmad Rifai',
                'jenis_kelamin' => 'L',
                'alamat' => 'Jl. Merdeka No. 12, Bandung',
                'no_telp' => '081234567801',
            ],
            [
                'user_id' => $userSiswa2->id,
                'kelas_id' => $kelasModels['X RPL 1']->id,
                'spp_id' => $spp2027['10']->id,
                'nisn' => '0071234562',
                'nis' => '2026002',
                'nama' => 'Siti Nurhaliza',
                'jenis_kelamin' => 'P',
                'alamat' => 'Jl. Asia Afrika No. 45, Bandung',
                'no_telp' => '081234567802',
            ],
            [
                'user_id' => null,
                'kelas_id' => $kelasModels['X RPL 2']->id,
                'spp_id' => $spp2027['10']->id,
                'nisn' => '0071234563',
                'nis' => '2026003',
                'nama' => 'Bayu Pratama',
                'jenis_kelamin' => 'L',
                'alamat' => 'Jl. Riau No. 88, Bandung',
                'no_telp' => '081234567803',
            ],
            [
                'user_id' => null,
                'kelas_id' => $kelasModels['XI RPL 1']->id,
                'spp_id' => $spp2027['11']->id,
                'nisn' => '0061234564',
                'nis' => '2025010',
                'nama' => 'Dewi Lestari',
                'jenis_kelamin' => 'P',
                'alamat' => 'Jl. Dago No. 102, Bandung',
                'no_telp' => '081234567804',
            ],
            [
                'user_id' => null,
                'kelas_id' => $kelasModels['XII RPL 1']->id,
                'spp_id' => $spp2027['12']->id,
                'nisn' => '0051234565',
                'nis' => '2024015',
                'nama' => 'Fajar Nugraha',
                'jenis_kelamin' => 'L',
                'alamat' => 'Jl. Buah Batu No. 19, Bandung',
                'no_telp' => '081234567805',
            ],
            [
                'user_id' => null,
                'kelas_id' => $kelasModels['X TKJ 1']->id,
                'spp_id' => $spp2027['10']->id,
                'nisn' => '0071234566',
                'nis' => '2026020',
                'nama' => 'Gita Gutawa',
                'jenis_kelamin' => 'P',
                'alamat' => 'Jl. Setiabudi No. 50, Bandung',
                'no_telp' => '081234567806',
            ],
        ];

        $createdSiswas = [];
        foreach ($siswaData as $data) {
            $createdSiswas[] = Siswa::firstOrCreate(['nisn' => $data['nisn']], $data);
        }

        foreach ($createdSiswas as $siswa) {
            $tarif = Spp::untukKelas($siswa->kelas);

            if ($tarif) {
                $siswa->update(['spp_id' => $tarif->id]);
            }
        }

        // 7. Seed Sample Payments
        $bulanSamples = ['Juli', 'Agustus', 'September', 'Oktober'];
        $counter = 1;

        foreach ($createdSiswas as $index => $siswa) {
            // pay up to index+1 months
            $monthsToPay = array_slice($bulanSamples, 0, min($index + 2, count($bulanSamples)));
            foreach ($monthsToPay as $bulan) {
                Pembayaran::firstOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'bulan_dibayar' => $bulan,
                        'tahun_dibayar' => 2026,
                    ],
                    [
                        'kode_transaksi' => 'SPP-20261102-'.str_pad((string) $counter++, 4, '0', STR_PAD_LEFT),
                        'user_id' => $petugas->id,
                        'spp_id' => $siswa->spp_id,
                        'tgl_bayar' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                        'jumlah_bayar' => $siswa->spp->nominal,
                        'metode_pembayaran' => $counter % 2 === 0 ? 'Tunai' : 'Transfer Bank',
                        'catatan' => 'Pembayaran SPP Bulan '.$bulan.' 2026',
                    ]
                );
            }
        }
    }

    /**
     * Create the same SPP nominal for levels X, XI, and XII.
     *
     * @return array<string, Spp>
     */
    private function buatTarifSpp(int $tahun, string $tahunAjaran, int $nominal, string $keterangan): array
    {
        $tarifs = [];

        foreach (Spp::TINGKAT_LIST as $tingkat => $label) {
            $tarifs[$tingkat] = Spp::firstOrCreate(
                [
                    'kategori' => Spp::KATEGORI_SPP,
                    'tahun' => $tahun,
                    'tahun_ajaran' => $tahunAjaran,
                    'tingkat' => $tingkat,
                ],
                [
                    'tipe' => Spp::TIPE_BULANAN,
                    'nominal' => $nominal,
                    'keterangan' => $keterangan,
                ]
            );
        }

        return $tarifs;
    }
}
