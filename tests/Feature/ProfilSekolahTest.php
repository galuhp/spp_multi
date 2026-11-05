<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\ProfilSekolah;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilSekolahTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $petugas;

    protected User $siswaUser;

    protected Siswa $siswa;

    protected Pembayaran $pembayaran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_test',
            'role' => UserRole::Admin,
        ]);

        $this->petugas = User::factory()->create([
            'username' => 'petugas_test',
            'role' => UserRole::Petugas,
        ]);

        $jurusan = Jurusan::create([
            'kode' => 'TKJ',
            'nama' => 'Teknik Komputer Jaringan',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'XII TKJ 1',
            'tingkat' => '12',
            'jurusan_id' => $jurusan->id,
        ]);

        $spp = Spp::create([
            'tahun' => 2025,
            'tahun_ajaran' => '2025/2026',
            'nominal' => 300000,
            'kategori' => Spp::KATEGORI_SPP,
        ]);

        $this->siswaUser = User::factory()->create([
            'username' => '1234567890',
            'role' => UserRole::Siswa,
        ]);

        $this->siswa = Siswa::create([
            'user_id' => $this->siswaUser->id,
            'nisn' => '1234567890',
            'nis' => '12345',
            'nama' => 'Budi Santoso',
            'kelas_id' => $kelas->id,
            'spp_id' => $spp->id,
            'alamat' => 'Jl. Test No. 1',
            'no_telp' => '08123456789',
        ]);

        $this->pembayaran = Pembayaran::create([
            'kode_transaksi' => 'SPP-20261103-0001',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $spp->id,
            'tgl_bayar' => now()->toDateString(),
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => '2025',
            'jumlah_bayar' => 300000,
            'metode_pembayaran' => 'Tunai',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('sekolah.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_profil_sekolah_settings(): void
    {
        // Petugas forbidden
        $responsePetugas = $this->actingAs($this->petugas)->get(route('sekolah.edit'));
        $responsePetugas->assertForbidden();

        // Siswa forbidden
        $responseSiswa = $this->actingAs($this->siswaUser)->get(route('sekolah.edit'));
        $responseSiswa->assertForbidden();
    }

    public function test_admin_can_view_profil_sekolah_edit_page(): void
    {
        $profil = ProfilSekolah::current();

        $response = $this->actingAs($this->admin)->followingRedirects()->get(route('sekolah.edit'));

        $response->assertOk();
        $response->assertSee('Profil Sekolah & Kop Surat');
        $response->assertSee($profil->nama_sekolah);
        $response->assertSee($profil->nama_kepala_sekolah);
    }

    public function test_admin_can_update_profil_sekolah_data(): void
    {
        $payload = [
            'nama_sekolah' => 'SMK TI GARUDA NUSANTARA',
            'npsn' => '69987654',
            'jenjang' => 'SMK',
            'pemerintah_daerah' => 'Pemerintah Provinsi Jawa Barat',
            'dinas_pendidikan' => 'Cabang Dinas Pendidikan Wilayah VII',
            'alamat' => 'Jl. Garuda No. 88, Cibiru',
            'kota' => 'Bandung',
            'kode_pos' => '40614',
            'no_telp' => '(022) 7890123',
            'email' => 'info@smktigaruda.sch.id',
            'website' => 'https://smktigaruda.sch.id',
            'nama_kepala_sekolah' => 'Dr. H. Ahmad Fauzi, M.T.',
            'nip_kepala_sekolah' => '19750505 200003 1 005',
        ];

        $response = $this->actingAs($this->admin)->put(route('sekolah.update'), $payload);

        $response->assertRedirect(route('sekolah.edit'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('profil_sekolahs', [
            'nama_sekolah' => 'SMK TI GARUDA NUSANTARA',
            'npsn' => '69987654',
            'kota' => 'Bandung',
            'nama_kepala_sekolah' => 'Dr. H. Ahmad Fauzi, M.T.',
        ]);

        // Verifikasi log aktivitas tercatat
        $this->assertDatabaseHas('log_aktivitas', [
            'user_id' => $this->admin->id,
            'aksi' => 'sekolah.diubah',
        ]);
    }

    public function test_updated_profil_sekolah_is_rendered_in_receipt_and_report(): void
    {
        $profil = ProfilSekolah::current();
        $profil->update([
            'nama_sekolah' => 'SMK TELKOM INFORMATIKA',
            'alamat' => 'Jl. Telekomunikasi No. 99',
            'kota' => 'Cimahi',
            'no_telp' => '(022) 5556677',
            'nama_kepala_sekolah' => 'Ir. Budi Rahardjo, Ph.D.',
            'nip_kepala_sekolah' => '19800101 200501 1 009',
        ]);
        ProfilSekolah::clearCache();

        // 1. Kuitansi Cetak
        $responseCetak = $this->actingAs($this->admin)->get(route('pembayaran.cetak', $this->pembayaran->id));
        $responseCetak->assertOk();
        $responseCetak->assertSee('SMK TELKOM INFORMATIKA');
        $responseCetak->assertSee('(022) 5556677');
        $responseCetak->assertSee('Cimahi');

        // 2. Kuitansi Show redirects to the printable receipt
        $this->actingAs($this->admin)
            ->get(route('pembayaran.show', $this->pembayaran->id))
            ->assertRedirect(route('pembayaran.cetak', $this->pembayaran->id));

        $responseShow = $this->actingAs($this->admin)->followingRedirects()->get(route('pembayaran.show', $this->pembayaran->id));
        $responseShow->assertOk();
        $responseShow->assertSee('SMK TELKOM INFORMATIKA');

        // 3. Bukti Verifikasi Digital Publik
        $responseBukti = $this->get($this->pembayaran->buktiUrl());
        $responseBukti->assertOk();
        $responseBukti->assertSee('SMK TELKOM INFORMATIKA');

        // 4. Laporan Cetak Resmi
        $responseLaporan = $this->actingAs($this->admin)->get(route('laporan.cetak', [
            'tgl_awal' => now()->subDay()->toDateString(),
            'tgl_akhir' => now()->addDay()->toDateString(),
        ]));
        $responseLaporan->assertOk();
        $responseLaporan->assertSee('SMK TELKOM INFORMATIKA');
        $responseLaporan->assertSee('Ir. Budi Rahardjo, Ph.D.');
        $responseLaporan->assertSee('19800101 200501 1 009');
        $responseLaporan->assertSee('Cimahi');
    }
}
