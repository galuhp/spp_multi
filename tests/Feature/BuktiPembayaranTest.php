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

class BuktiPembayaranTest extends TestCase
{
    use RefreshDatabase;

    protected User $petugas;
    protected User $siswaUser;
    protected Siswa $siswa;
    protected Spp $spp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = User::factory()->create([
            'name' => 'Siti Aminah',
            'username' => 'petugas_qr',
            'role' => UserRole::Petugas,
        ]);

        $jurusan = Jurusan::create([
            'kode' => 'TKJ',
            'nama' => 'Teknik Komputer dan Jaringan',
            'deskripsi' => 'Jaringan Komputer',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'XI TKJ 2',
            'tingkat' => '11',
            'jurusan_id' => $jurusan->id,
        ]);

        $this->spp = Spp::create([
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'nominal' => 300000,
            'keterangan' => 'SPP TA 2026/2027',
        ]);

        $this->siswaUser = User::factory()->create([
            'username' => '0089876543',
            'role' => UserRole::Siswa,
        ]);

        $this->siswa = Siswa::create([
            'user_id' => $this->siswaUser->id,
            'kelas_id' => $kelas->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0089876543',
            'nis' => '2026105',
            'nama' => 'Rizky Ramadhan',
            'jenis_kelamin' => 'L',
            'alamat' => 'Jl. Merdeka No. 10',
            'no_telp' => '081298765432',
        ]);
    }

    public function test_printed_receipt_embeds_qr_code_and_public_bukti_link_for_staff_and_student(): void
    {
        $pembayaran = Pembayaran::create([
            'kode_transaksi' => 'SPP-20261103-0001',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-11-03',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 300000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        // Staff print view
        $staffResponse = $this->actingAs($this->petugas)
            ->get(route('pembayaran.cetak', $pembayaran));

        $staffResponse->assertStatus(200)
            ->assertSee('aria-label="QR Code Bukti Pembayaran"', false)
            ->assertSee('Scan Cek Bukti Asli')
            ->assertSee($pembayaran->buktiUrl(), false);

        // Student portal print view
        $studentResponse = $this->actingAs($this->siswaUser)
            ->get(route('portal.kuitansi', $pembayaran));

        $studentResponse->assertStatus(200)
            ->assertSee('aria-label="QR Code Bukti Pembayaran"', false)
            ->assertSee(route('portal.riwayat'), false);
    }

    public function test_parent_can_open_qr_bukti_pembayaran_link_without_logging_in(): void
    {
        $this->actingAs($this->petugas)->post('/pembayaran', [
            'siswa_id' => $this->siswa->id,
            'tahun_dibayar' => 2026,
            'bulan_dibayar' => ['Juli', 'Agustus'],
            'tgl_bayar' => '2026-11-03',
            'metode_pembayaran' => 'QRIS',
            'catatan' => 'Lunas 2 bulan',
        ]);

        // Log out completely to simulate parent scanning QR on their phone
        auth()->logout();

        $pembayaran = Pembayaran::where('siswa_id', $this->siswa->id)->orderBy('id')->firstOrFail();

        $response = $this->get($pembayaran->buktiUrl());

        $response->assertStatus(200)
            ->assertSee('Bukti Pembayaran Terverifikasi Sah')
            ->assertSee($pembayaran->kode_transaksi)
            ->assertSee('Rizky Ramadhan')
            ->assertSee('0089876543')
            ->assertSee('XI TKJ 2')
            ->assertSee('Juli 2026')
            ->assertSee('Agustus 2026')
            ->assertSee('Rp 600.000');
    }

    public function test_tampered_or_unknown_qr_token_returns_404_and_hides_student_data(): void
    {
        $pembayaran = Pembayaran::create([
            'kode_transaksi' => 'SPP-20261103-0042',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-11-03',
            'bulan_dibayar' => 'September',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 300000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $tamperedResponse = $this->get(route('bukti.pembayaran', [
            'kode' => $pembayaran->kode_transaksi,
            'token' => '0000000000000000',
        ]));

        $tamperedResponse->assertStatus(404)
            ->assertSee('Bukti Pembayaran Tidak Valid')
            ->assertDontSee('Rizky Ramadhan');

        $unknownResponse = $this->get(route('bukti.pembayaran', [
            'kode' => 'SPP-20990101-9999',
            'token' => $pembayaran->verificationToken(),
        ]));

        $unknownResponse->assertStatus(404)
            ->assertSee('Bukti Pembayaran Tidak Valid');
    }
}
