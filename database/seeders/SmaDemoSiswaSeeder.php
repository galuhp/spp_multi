<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\Tenant;
use App\Models\Wali;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;

class SmaDemoSiswaSeeder extends Seeder
{
    /**
     * Seed 5 siswa demo pada tenant SMA Demo (atau tenant pertama).
     */
    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('slug', 'sma-demo')
            ->orWhere('name', 'like', '%SMA Demo%')
            ->orderBy('id')
            ->first()
            ?? Tenant::query()->orderBy('id')->first();

        if (! $tenant) {
            $this->command?->error('Tenant tidak ditemukan. Buat tenant terlebih dahulu.');

            return;
        }

        TenantContext::set($tenant);

        try {
            $tahunAjaran = TahunAjaran::query()->where('aktif', true)->first()
                ?? TahunAjaran::query()->first();

            if (! $tahunAjaran) {
                $namaTa = TahunAjaran::withoutGlobalScope('tenant')->where('nama', '2026/2027')->exists()
                    ? "2026/2027-T{$tenant->id}"
                    : '2026/2027';

                $tahunAjaran = TahunAjaran::create([
                    'nama' => $namaTa,
                    'tanggal_mulai' => '2026-07-01',
                    'tanggal_selesai' => '2027-06-30',
                    'aktif' => true,
                ]);
            }

            $jurusan = Jurusan::query()->first();

            if (! $jurusan) {
                $kodeJurusan = Jurusan::withoutGlobalScope('tenant')->where('kode', 'IPA')->exists()
                    ? "IPA-T{$tenant->id}"
                    : 'IPA';

                $jurusan = Jurusan::create([
                    'kode' => $kodeJurusan,
                    'nama' => 'Ilmu Pengetahuan Alam',
                    'deskripsi' => 'Program peminatan Ilmu Pengetahuan Alam.',
                ]);
            }

            $kelas = Kelas::query()->first()
                ?? Kelas::create([
                    'nama_kelas' => 'X IPA 1',
                    'tingkat' => '10',
                    'jurusan_id' => $jurusan->id,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                ]);

            $spp = Spp::query()->where('kategori', 'SPP')->first()
                ?? Spp::create([
                    'kategori' => 'SPP',
                    'tipe' => 'bulanan',
                    'nama' => 'SPP Bulanan',
                    'tahun' => 2026,
                    'tahun_ajaran' => $tahunAjaran->nama,
                    'tingkat' => null,
                    'nominal' => 150000,
                    'keterangan' => "Tarif SPP Bulanan TA {$tahunAjaran->nama}",
                ]);

            $tenantPrefix = str_pad((string) $tenant->id, 2, '0', STR_PAD_LEFT);

            $daftarSiswa = [
                ['Ahmad Fauzan', 'L', "30{$tenantPrefix}0001", "26{$tenantPrefix}01", 'Jl. Merdeka No. 10'],
                ['Siti Nurhaliza', 'P', "30{$tenantPrefix}0002", "26{$tenantPrefix}02", 'Jl. Sudirman No. 14'],
                ['Rizky Pratama', 'L', "30{$tenantPrefix}0003", "26{$tenantPrefix}03", 'Jl. Ahmad Yani No. 22'],
                ['Dewi Lestari', 'P', "30{$tenantPrefix}0004", "26{$tenantPrefix}04", 'Jl. Diponegoro No. 5'],
                ['Bima Saputra', 'L', "30{$tenantPrefix}0005", "26{$tenantPrefix}05", 'Jl. Kartini No. 18'],
            ];

            foreach ($daftarSiswa as [$nama, $jk, $nisn, $nis, $alamat]) {
                $siswa = Siswa::withoutEvents(fn () => Siswa::updateOrCreate(
                    ['nisn' => $nisn],
                    [
                        'tenant_id' => $tenant->id,
                        'kelas_id' => $kelas->id,
                        'spp_id' => $spp->id,
                        'nis' => $nis,
                        'nama' => $nama,
                        'jenis_kelamin' => $jk,
                        'alamat' => $alamat,
                        'no_telp' => '0812345'.substr($nisn, -5),
                        'tanggal_masuk' => '2026-07-01',
                        'tahun_masuk' => 2026,
                        'status' => 'aktif',
                        'is_archived' => false,
                    ]
                ));

                Wali::updateOrCreate(
                    ['siswa_id' => $siswa->id],
                    [
                        'nama_ayah' => 'Bapak '.explode(' ', $nama)[0],
                        'nama_ibu' => 'Ibu '.explode(' ', $nama)[0],
                        'hp_wali' => '0813456'.substr($nisn, -5),
                    ]
                );

                RiwayatKelas::updateOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                    ],
                    [
                        'kelas_id' => $kelas->id,
                        'spp_id' => $spp->id,
                        'status' => 'aktif',
                    ]
                );
            }

            $this->command?->info("Berhasil seed 5 siswa untuk tenant: {$tenant->name} ({$tenant->slug}).");
        } finally {
            TenantContext::clear();
        }
    }
}
