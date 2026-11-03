<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SppSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $petugas;
    protected User $siswaUser;
    protected Jurusan $jurusan;
    protected Kelas $kelas;
    protected Spp $spp;
    protected Siswa $siswa;

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

        $this->jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
            'deskripsi' => 'Pengembangan Web dan Software',
        ]);

        $this->kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ]);

        $this->spp = Spp::create([
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'nominal' => 250000,
            'keterangan' => 'Tarif SPP TA 2026/2027',
        ]);

        $this->siswaUser = User::factory()->create([
            'username' => '0071234560',
            'role' => UserRole::Siswa,
        ]);

        $this->siswa = Siswa::create([
            'user_id' => $this->siswaUser->id,
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0071234560',
            'nis' => '2026001',
            'nama' => 'Budi Pratama',
            'jenis_kelamin' => 'L',
            'alamat' => 'Bandung',
            'no_telp' => '081234567890',
        ]);
    }

    public function test_admin_can_access_dashboard_and_master_data(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Utama');

        $jurusanResponse = $this->actingAs($this->admin)->get('/jurusan');
        $jurusanResponse->assertStatus(200);

        $kelasResponse = $this->actingAs($this->admin)->get('/kelas');
        $kelasResponse->assertStatus(200);

        $sppResponse = $this->actingAs($this->admin)->get('/spp');
        $sppResponse->assertStatus(200);

        $userResponse = $this->actingAs($this->admin)->get('/user');
        $userResponse->assertStatus(200);
    }

    public function test_petugas_cannot_access_admin_only_master_data(): void
    {
        $response = $this->actingAs($this->petugas)->get('/jurusan');
        $response->assertStatus(403);

        $responseKelas = $this->actingAs($this->petugas)->get('/kelas');
        $responseKelas->assertStatus(403);

        $responseSpp = $this->actingAs($this->petugas)->get('/spp');
        $responseSpp->assertStatus(403);

        $responseUser = $this->actingAs($this->petugas)->get('/user');
        $responseUser->assertStatus(403);
    }

    public function test_petugas_can_view_siswa_and_record_payment(): void
    {
        $siswaResponse = $this->actingAs($this->petugas)->get('/siswa');
        $siswaResponse->assertStatus(200);
        $siswaResponse->assertSee($this->siswa->nama);

        // Record payment for Juli and Agustus 2026
        $paymentResponse = $this->actingAs($this->petugas)->post('/pembayaran', [
            'siswa_id' => $this->siswa->id,
            'tahun_dibayar' => 2026,
            'bulan_dibayar' => ['Juli', 'Agustus'],
            'tgl_bayar' => '2026-11-02',
            'metode_pembayaran' => 'Tunai',
            'catatan' => 'Pembayaran SPP 2 Bulan',
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswa->id,
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswa->id,
            'bulan_dibayar' => 'Agustus',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
        ]);

        $firstPayment = Pembayaran::where('siswa_id', $this->siswa->id)->orderBy('id', 'asc')->first();
        $paymentResponse->assertRedirect(route('pembayaran.show', $firstPayment->id));

        // Receipt view
        $receiptResponse = $this->actingAs($this->petugas)->get(route('pembayaran.show', $firstPayment->id));
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('Kuitansi');

        // Printable receipt view
        $printResponse = $this->actingAs($this->petugas)->get(route('pembayaran.cetak', $firstPayment->id));
        $printResponse->assertStatus(200);
    }

    public function test_duplicate_payment_for_same_month_is_prevented(): void
    {
        // First payment
        $this->actingAs($this->petugas)->post('/pembayaran', [
            'siswa_id' => $this->siswa->id,
            'tahun_dibayar' => 2026,
            'bulan_dibayar' => ['Juli'],
            'tgl_bayar' => '2026-11-02',
            'metode_pembayaran' => 'Tunai',
        ]);

        // Attempt duplicate
        $duplicateResponse = $this->actingAs($this->petugas)->post('/pembayaran', [
            'siswa_id' => $this->siswa->id,
            'tahun_dibayar' => 2026,
            'bulan_dibayar' => ['Juli'],
            'tgl_bayar' => '2026-11-02',
            'metode_pembayaran' => 'Tunai',
        ]);

        $duplicateResponse->assertSessionHas('error');
        $this->assertEquals(1, Pembayaran::where('siswa_id', $this->siswa->id)->where('bulan_dibayar', 'Juli')->count());
    }

    public function test_laporan_can_be_generated_and_printed(): void
    {
        // Create a transaction first
        Pembayaran::create([
            'kode_transaksi' => 'SPP-20261102-9999',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-11-02',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $laporanResponse = $this->actingAs($this->petugas)->get('/laporan');
        $laporanResponse->assertStatus(200);
        $laporanResponse->assertSee('Rekapitulasi Pembayaran SPP');

        $cetakResponse = $this->actingAs($this->petugas)->get('/laporan/cetak');
        $cetakResponse->assertStatus(200);
        $cetakResponse->assertSee('LAPORAN REKAPITULASI');
    }

    public function test_siswa_can_view_own_tagihan_and_history(): void
    {
        $response = $this->actingAs($this->siswaUser)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Portal Informasi SPP Siswa');

        $tagihanResponse = $this->actingAs($this->siswaUser)->get('/portal/tagihan');
        $tagihanResponse->assertStatus(200);
        $tagihanResponse->assertSee($this->siswa->nama);

        $riwayatResponse = $this->actingAs($this->siswaUser)->get('/portal/riwayat');
        $riwayatResponse->assertStatus(200);
    }
}
