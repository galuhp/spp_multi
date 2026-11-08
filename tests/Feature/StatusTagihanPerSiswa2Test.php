<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\StatusTagihanPerSiswa2;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StatusTagihanPerSiswa2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugas;

    private TahunAjaran $tahunAjaran;

    private Jurusan $jurusan;

    private Kelas $kelas;

    private Spp $spp;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-07 09:00:00'));

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->petugas = User::factory()->create(['role' => UserRole::Petugas]);

        RolePermission::query()->firstOrCreate([
            'role' => UserRole::Admin,
            'permission' => RolePermission::PORTAL_SISWA,
        ]);
        RolePermission::query()->firstOrCreate([
            'role' => UserRole::Petugas,
            'permission' => RolePermission::PORTAL_SISWA,
        ]);

        $this->tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
        ]);

        $this->jurusan = Jurusan::query()->create([
            'kode' => 'TKJ',
            'nama' => 'Teknik Komputer dan Jaringan',
        ]);

        $this->kelas = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => 'XII TKJ 1',
            'tingkat' => '12',
        ]);

        $this->spp = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'SPP Kelas XII 2026/2027',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '12',
            'nominal' => 200000,
        ]);

        $this->siswa = Siswa::query()->create([
            'nisn' => '0098765432',
            'nis' => '12345',
            'nama' => 'Budi Santoso',
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->spp->id,
            'alamat' => 'Jl. Pahlawan No. 10',
            'no_telp' => '081234567890',
            'status' => 'aktif',
        ]);
    }

    public function test_admin_and_staff_can_access_status_tagihan_per_siswa_2(): void
    {
        $this->actingAs($this->admin)
            ->get(StatusTagihanPerSiswa2::getUrl())
            ->assertStatus(200)
            ->assertSee('Status Tagihan Per Siswa 2')
            ->assertSee('Rincian Tagihan Siswa');

        $this->actingAs($this->petugas)
            ->get(StatusTagihanPerSiswa2::getUrl())
            ->assertStatus(200);
    }

    public function test_filter_pilih_siswa_berfungsi(): void
    {
        $siswa2 = Siswa::query()->create([
            'nisn' => '0098765433',
            'nis' => '12346',
            'nama' => 'Citra Lestari',
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->spp->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(StatusTagihanPerSiswa2::class)
            ->set('cara_pilih', 'cari')
            ->set('cari', 'Citra')
            ->assertSee('Citra Lestari')
            ->call('pilihSiswa', $siswa2->id)
            ->assertSet('selectedSiswaId', $siswa2->id)
            ->assertSee('Citra Lestari');
    }

    public function test_tagihan_data_menampilkan_pos_tarif_status_dan_tanggal_bayar(): void
    {
        // Bayar SPP bulan Juli
        Pembayaran::query()->create([
            'kode_transaksi' => 'TRX-TEST-001',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-07-15',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 200000,
            'metode_pembayaran' => 'Tunai',
        ]);

        // Buat tarif non-SPP: Uang Gedung
        $uangGedung = Spp::query()->create([
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Uang Gedung XII',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '12',
            'nominal' => 500000,
        ]);

        $this->actingAs($this->admin);

        $component = Livewire::test(StatusTagihanPerSiswa2::class, ['siswa_id' => $this->siswa->id]);

        $items = collect($component->instance()->tagihanData['items']);

        // Cek SPP Juli
        $sppJuli = $items->firstWhere('pos_tarif', 'SPP Bulan Juli 2026');
        $this->assertNotNull($sppJuli);
        $this->assertSame('lunas', $sppJuli['status']);
        $this->assertSame('15/07/2026', $sppJuli['tanggal_bayar']);
        $this->assertSame(200000, $sppJuli['dibayar']);
        $this->assertSame(0, $sppJuli['sisa']);

        // Cek SPP Agustus (Belum Lunas)
        $sppAgustus = $items->firstWhere('pos_tarif', 'SPP Bulan Agustus 2026');
        $this->assertNotNull($sppAgustus);
        $this->assertSame('belum', $sppAgustus['status']);
        $this->assertNull($sppAgustus['tanggal_bayar']);
        $this->assertSame(200000, $sppAgustus['sisa']);

        // Cek Non-SPP Uang Gedung
        $itemGedung = $items->firstWhere('pos_tarif', 'Uang Gedung XII');
        $this->assertNotNull($itemGedung);
        $this->assertSame('belum', $itemGedung['status']);
        $this->assertSame(500000, $itemGedung['nominal']);

        // Assert rendered view displays the table contents
        $component
            ->assertSee('SPP Bulan Juli 2026')
            ->assertSee('15/07/2026')
            ->assertSee('Lunas')
            ->assertSee('Uang Gedung XII')
            ->assertSee('Belum Lunas');
    }

    public function test_filter_status_di_tabel_rincian(): void
    {
        // Bayar SPP bulan Juli
        Pembayaran::query()->create([
            'kode_transaksi' => 'TRX-TEST-002',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-07-20',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 200000,
            'metode_pembayaran' => 'Transfer',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(StatusTagihanPerSiswa2::class, ['siswa_id' => $this->siswa->id])
            ->call('setStatusFilter', 'lunas')
            ->assertSee('SPP Bulan Juli 2026')
            ->assertDontSee('SPP Bulan Agustus 2026')
            ->call('setStatusFilter', 'belum_lunas')
            ->assertSee('SPP Bulan Agustus 2026')
            ->assertDontSee('SPP Bulan Juli 2026');
    }

    public function test_siswa_dapat_melihat_tagihannya_sendiri(): void
    {
        $userSiswa = User::factory()->create([
            'username' => $this->siswa->nisn,
            'role' => UserRole::Siswa,
        ]);

        $this->actingAs($userSiswa)
            ->get(StatusTagihanPerSiswa2::getUrl())
            ->assertStatus(200)
            ->assertSee($this->siswa->nama)
            ->assertSee('0098765432')
            ->assertDontSee('Pilih Siswa untuk Ditinjau'); // Filter tersembunyi untuk akun siswa
    }
}
