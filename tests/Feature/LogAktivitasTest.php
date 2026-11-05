<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogAktivitasTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $petugas;

    protected Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['username' => 'admin_log', 'role' => UserRole::Admin]);
        $this->petugas = User::factory()->create(['username' => 'petugas_log', 'role' => UserRole::Petugas]);

        $jurusan = Jurusan::create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak']);
        $kelas = Kelas::create(['nama_kelas' => 'X RPL 1', 'tingkat' => '10', 'jurusan_id' => $jurusan->id]);
        $spp = Spp::create(['tahun' => 2026, 'tahun_ajaran' => '2026/2027', 'nominal' => 250000]);

        $this->siswa = Siswa::create([
            'kelas_id' => $kelas->id,
            'spp_id' => $spp->id,
            'nisn' => '0071234560',
            'nis' => '2026001',
            'nama' => 'Budi Pratama',
            'jenis_kelamin' => 'L',
        ]);
    }

    public function test_creating_payment_is_logged_with_user_and_ip(): void
    {
        $this->actingAs($this->petugas)->post('/pembayaran', [
            'siswa_id' => $this->siswa->id,
            'tahun_dibayar' => 2026,
            'bulan_dibayar' => ['Juli', 'Agustus'],
            'tgl_bayar' => '2026-11-02',
            'metode_pembayaran' => 'Tunai',
        ]);

        $this->assertEquals(2, LogAktivitas::where('aksi', 'pembayaran.dibuat')->count());

        $log = LogAktivitas::where('aksi', 'pembayaran.dibuat')->first();
        $this->assertEquals($this->petugas->id, $log->user_id);
        $this->assertEquals('Pembayaran', $log->subjek_tipe);
        $this->assertNotNull($log->ip_address);
        $this->assertEquals(250000, $log->data['jumlah_bayar']);
    }

    public function test_deleting_payment_is_logged_before_removal(): void
    {
        $pembayaran = Pembayaran::create([
            'kode_transaksi' => 'SPP-20261102-0001',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->siswa->spp_id,
            'tgl_bayar' => '2026-11-02',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $this->actingAs($this->admin)->delete(route('pembayaran.destroy', $pembayaran->id));

        $this->assertDatabaseMissing('pembayarans', ['id' => $pembayaran->id]);

        $log = LogAktivitas::where('aksi', 'pembayaran.dihapus')->first();
        $this->assertNotNull($log);
        $this->assertEquals($this->admin->id, $log->user_id);
        $this->assertEquals('SPP-20261102-0001', $log->data['kode_transaksi']);
    }

    public function test_spp_update_logs_before_and_after_values(): void
    {
        $spp = Spp::first();

        $this->actingAs($this->admin)->put(route('spp.update', $spp->id), [
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 300000,
        ]);

        $log = LogAktivitas::where('aksi', 'spp.diubah')->first();
        $this->assertNotNull($log);
        $this->assertEquals(250000, $log->data['sebelum']['nominal']);
        $this->assertEquals(300000, $log->data['sesudah']['nominal']);
    }

    public function test_user_log_never_stores_password(): void
    {
        $this->actingAs($this->admin)->post(route('user.store'), [
            'name' => 'Kasir Baru',
            'username' => 'kasir_baru',
            'password' => 'rahasia123',
            'role' => 'petugas',
        ]);

        $log = LogAktivitas::where('aksi', 'user.dibuat')->first();
        $this->assertNotNull($log);
        $this->assertStringNotContainsString('rahasia123', json_encode($log->data));
        $this->assertArrayNotHasKey('password', $log->data);
    }

    public function test_only_admin_can_view_activity_log(): void
    {
        $this->actingAs($this->admin)->get('/log-aktivitas')->assertStatus(200)->assertSee('Log Aktivitas');
        $this->actingAs($this->petugas)->get('/log-aktivitas')->assertStatus(403);
    }
}
