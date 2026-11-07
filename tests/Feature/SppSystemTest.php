<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Jurusans\JurusanResource;
use App\Filament\Resources\Kelas\KelasResource;
use App\Filament\Resources\Spps\SppResource;
use App\Filament\Resources\Users\UserResource;
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
        $this->actingAs($this->admin)->get('/dashboard')->assertRedirect('/admin');
        $this->actingAs($this->admin)->get('/jurusan')->assertRedirect(JurusanResource::getUrl('index'));
        $this->actingAs($this->admin)->get('/kelas')->assertRedirect(KelasResource::getUrl('index'));
        $this->actingAs($this->admin)->get('/spp')->assertRedirect(SppResource::getUrl('index'));
        $this->actingAs($this->admin)->get('/user')->assertRedirect(UserResource::getUrl('index'));
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
        $siswaResponse = $this->actingAs($this->petugas)->followingRedirects()->get('/siswa');
        $siswaResponse->assertOk();
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

        // Receipt detail redirects to the printable receipt.
        $this->actingAs($this->petugas)
            ->get(route('pembayaran.show', $firstPayment->id))
            ->assertRedirect(route('pembayaran.cetak', $firstPayment->id));

        $receiptResponse = $this->actingAs($this->petugas)->followingRedirects()->get(route('pembayaran.show', $firstPayment->id));
        $receiptResponse->assertOk();
        $receiptResponse->assertSee('Kuitansi');

        // Printable receipt view
        $printResponse = $this->actingAs($this->petugas)->get(route('pembayaran.cetak', $firstPayment->id));
        $printResponse->assertStatus(200);
    }

    public function test_payment_form_flows_from_class_to_student_to_months(): void
    {
        // Step 1: the create form lists classes
        $this->actingAs($this->petugas)->followingRedirects()->get('/pembayaran/create')
            ->assertOk()
            ->assertSee('Pilih Siswa')
            ->assertSee($this->kelas->nama_kelas);

        // Step 2: students of the chosen class are listed
        $this->actingAs($this->petugas)->followingRedirects()->get('/pembayaran/create?kelas_id='.$this->kelas->id)
            ->assertOk()
            ->assertSee($this->siswa->nama);

        // Step 3: opening a student shows the SPP month options
        $this->actingAs($this->petugas)->followingRedirects()->get('/pembayaran/create?siswa_id='.$this->siswa->id)
            ->assertOk()
            ->assertSee('Pilih Bulan SPP yang Dibayar')
            ->assertSee('Juli');
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

        $laporanResponse = $this->actingAs($this->petugas)->followingRedirects()->get('/laporan');
        $laporanResponse->assertOk();
        $laporanResponse->assertSee('Rekapitulasi Pembayaran');
        $laporanResponse->assertSee('SPP-20261102-9999');

        $cetakResponse = $this->actingAs($this->petugas)->get('/laporan/cetak');
        $cetakResponse->assertStatus(200);
        $cetakResponse->assertSee('LAPORAN REKAPITULASI');
    }

    public function test_siswa_can_view_own_tagihan_and_history(): void
    {
        $response = $this->actingAs($this->siswaUser)->followingRedirects()->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Matriks Tagihan & Status Pembayaran');
        $response->assertSee($this->siswa->nama);

        $tagihanResponse = $this->actingAs($this->siswaUser)->followingRedirects()->get('/portal/tagihan');
        $tagihanResponse->assertOk();
        $tagihanResponse->assertSee($this->siswa->nama);

        $riwayatResponse = $this->actingAs($this->siswaUser)->followingRedirects()->get('/portal/riwayat');
        $riwayatResponse->assertOk();
        $riwayatResponse->assertSee('Riwayat Pembayaran SPP Saya');
        $riwayatResponse->assertSee($this->siswa->nama);
    }

    public function test_bukti_pembayaran_valid_meskipun_satu_kode_transaksi_memiliki_banyak_baris(): void
    {
        $baris1 = Pembayaran::create([
            'kode_transaksi' => 'SPP-20261107-0007',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-11-07',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $baris2 = Pembayaran::create([
            'kode_transaksi' => 'SPP-20261107-0007',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-11-07',
            'bulan_dibayar' => 'Agustus',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        // Baik diakses dari URL baris ke-1, baris ke-2, maupun token lama berbasis ID baris ke-2 tetap valid
        $this->get($baris1->buktiUrl())
            ->assertOk()
            ->assertSee('Bukti Pembayaran Terverifikasi Sah')
            ->assertSee('SPP-20261107-0007')
            ->assertSee('Rp 500.000');

        $this->get($baris2->buktiUrl())
            ->assertOk()
            ->assertSee('Bukti Pembayaran Terverifikasi Sah');

        $this->get(route('bukti.pembayaran', [
            'kode' => 'SPP-20261107-0007',
            'token' => $baris2->legacyVerificationToken(),
        ]))
            ->assertOk()
            ->assertSee('Bukti Pembayaran Terverifikasi Sah');
    }
}
