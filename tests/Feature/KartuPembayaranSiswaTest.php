<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\KartuPembayaranSiswa;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Laporan\LaporanSppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuPembayaranSiswaTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;

    private TahunAjaran $tahunAjaran;

    private Jurusan $jurusan;

    private Kelas $kelas;

    private Spp $tarif;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-05 09:00:00'));

        $this->petugas = User::factory()->create(['role' => UserRole::Petugas]);

        RolePermission::query()->firstOrCreate([
            'role' => UserRole::Petugas,
            'permission' => RolePermission::LAPORAN,
        ]);

        $this->tahunAjaran = TahunAjaran::query()->updateOrCreate(
            ['nama' => '2026/2027'],
            [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'aktif' => true,
            ],
        );

        $this->jurusan = Jurusan::query()->create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->kelas = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
        ]);

        $this->tarif = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'SPP',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 250000,
        ]);

        $this->siswa = Siswa::query()->create([
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->tarif->id,
            'nisn' => '0071234561',
            'nis' => 'NIS0071234561',
            'nama' => 'Ahmad Rifai',
            'jenis_kelamin' => 'L',
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_kartu_menampilkan_tagihan_pembayaran_dan_saldo_berjalan(): void
    {
        $gedung = Spp::query()->create([
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Uang Gedung',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 2000000,
        ]);

        $this->bayar($this->siswa, 'Juli', 250000);
        $this->bayar($this->siswa, 'Agustus', 100000);
        $this->bayar($this->siswa, 'September', 250000, void: true);
        $this->bayarNonSpp($this->siswa, $gedung, 500000);

        $kartu = app(LaporanSppService::class)->kartuPembayaran($this->siswa, $this->tahunAjaran);

        $this->assertSame(5000000, $kartu['ringkasan']['total_tagihan']);
        $this->assertSame(850000, $kartu['ringkasan']['total_dibayar']);
        $this->assertSame(4150000, $kartu['ringkasan']['saldo']);
        $this->assertSame(3000000, $kartu['ringkasan']['total_tagihan_spp']);
        $this->assertSame(2000000, $kartu['ringkasan']['total_tagihan_non_spp']);
        $this->assertSame(3250000, $kartu['ringkasan']['tagihan_jatuh_tempo']);
        $this->assertSame(2400000, $kartu['ringkasan']['tunggakan_jatuh_tempo']);
        $this->assertSame(250000, $kartu['ringkasan']['total_void']);

        $this->assertCount(16, $kartu['entries']);
        $this->assertCount(1, $kartu['void_payments']);
        $this->assertSame(4150000, $kartu['entries']->last()['saldo']);
        $this->assertSame('Tagihan SPP Juli 2026', $kartu['entries']->first()['uraian']);
        $this->assertTrue($kartu['entries']->contains(
            fn (array $entry): bool => $entry['tipe'] === 'pembayaran'
                && $entry['uraian'] === 'Pembayaran SPP Bulan Juli 2026'
                && $entry['kredit'] === 250000,
        ));
    }

    public function test_rincian_tagihan_menampilkan_pos_tarif_dan_sisa_per_pos(): void
    {
        $gedung = Spp::query()->create([
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Uang Gedung',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 2000000,
        ]);

        $this->bayar($this->siswa, 'Juli', 250000);
        $this->bayar($this->siswa, 'Agustus', 100000);
        $this->bayar($this->siswa, 'September', 250000, void: true);
        $this->bayarNonSpp($this->siswa, $gedung, 500000);

        $rincian = app(LaporanSppService::class)->rincianTagihan($this->siswa, $this->tahunAjaran);
        $items = collect($rincian['items']);

        $juli = $items->firstWhere('pos_tarif', 'SPP Bulan Juli 2026');
        $this->assertNotNull($juli);
        $this->assertSame('lunas', $juli['status']);
        $this->assertSame(250000, $juli['dibayar']);
        $this->assertSame(0, $juli['sisa']);

        $agustus = $items->firstWhere('pos_tarif', 'SPP Bulan Agustus 2026');
        $this->assertNotNull($agustus);
        $this->assertSame('sebagian', $agustus['status']);
        $this->assertSame(100000, $agustus['dibayar']);
        $this->assertSame(150000, $agustus['sisa']);

        $september = $items->firstWhere('pos_tarif', 'SPP Bulan September 2026');
        $this->assertNotNull($september);
        $this->assertSame('belum', $september['status']);

        $uangGedung = $items->firstWhere('pos_tarif', 'Uang Gedung');
        $this->assertNotNull($uangGedung);
        $this->assertSame(2000000, $uangGedung['nominal']);
        $this->assertSame(500000, $uangGedung['dibayar']);
        $this->assertSame(1500000, $uangGedung['sisa']);

        $this->assertSame(5000000, $rincian['total_nominal']);
        $this->assertSame(850000, $rincian['total_dibayar']);
        $this->assertSame(4150000, $rincian['total_sisa']);
    }

    public function test_halaman_kartu_ledger_nonaktif(): void
    {
        $this->actingAs($this->petugas)
            ->get(KartuPembayaranSiswa::getUrl())
            ->assertForbidden();
    }

    public function test_cetak_kartu_pembayaran_menampilkan_rincian_tagihan(): void
    {
        $bayarJuli = $this->bayar($this->siswa, 'Juli', 250000);
        $this->bayar($this->siswa, 'Agustus', 100000);
        $this->bayar($this->siswa, 'September', 250000, void: true);

        $this->actingAs($this->petugas)
            ->get(route('laporan.kartu.cetak', [
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'siswa_id' => $this->siswa->id,
            ]))
            ->assertOk()
            ->assertSee('KARTU PEMBAYARAN SISWA')
            ->assertSee($this->siswa->nama)
            ->assertSee('RINCIAN TAGIHAN')
            ->assertSee('Pos Tarif')
            ->assertSee('No. Kuitansi')
            ->assertSee($bayarJuli->kode_transaksi)
            ->assertSee('Transaksi Dibatalkan')
            ->assertSee('Mode Landscape')
            ->assertDontSee('LEMBAR PENGESAHAN')
            ->assertDontSee('Tarif SPP');
    }

    public function test_cetak_kartu_pembayaran_landscape_dapat_diakses_via_parameter_layout(): void
    {
        $bayarJuli = $this->bayar($this->siswa, 'Juli', 250000);

        $this->actingAs($this->petugas)
            ->get(route('laporan.kartu.cetak', [
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'siswa_id' => $this->siswa->id,
                'layout' => 'landscape',
            ]))
            ->assertOk()
            ->assertSee('KARTU PEMBAYARAN SISWA')
            ->assertSee('Mode Cetak Landscape')
            ->assertSee('A4 landscape')
            ->assertSee('Mode Portrait')
            ->assertSee('LEMBAR PENGESAHAN')
            ->assertSee($bayarJuli->kode_transaksi);
    }

    public function test_siswa_tidak_dapat_mengakses_kartu_pembayaran(): void
    {
        $siswaUser = User::factory()->create(['role' => UserRole::Siswa]);

        $this->actingAs($siswaUser)
            ->get(KartuPembayaranSiswa::getUrl())
            ->assertForbidden();

        $this->actingAs($siswaUser)
            ->get(route('laporan.kartu.cetak', [
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'siswa_id' => $this->siswa->id,
            ]))
            ->assertForbidden();
    }

    private function bayar(Siswa $siswa, string $bulan, int $jumlah, bool $void = false): Pembayaran
    {
        return Pembayaran::query()->create([
            'kode_transaksi' => 'TRX'.uniqid(),
            'user_id' => $this->petugas->id,
            'siswa_id' => $siswa->id,
            'spp_id' => $siswa->spp_id,
            'tgl_bayar' => '2026-11-05',
            'bulan_dibayar' => $bulan,
            'tahun_dibayar' => in_array($bulan, ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'], true) ? 2027 : 2026,
            'jumlah_bayar' => $jumlah,
            'metode_pembayaran' => 'Tunai',
            'void_at' => $void ? now() : null,
            'void_oleh' => $void ? $this->petugas->id : null,
            'void_alasan' => $void ? 'Koreksi transaksi' : null,
        ]);
    }

    private function bayarNonSpp(Siswa $siswa, Spp $tarif, int $jumlah): Pembayaran
    {
        return Pembayaran::query()->create([
            'kode_transaksi' => 'TRX'.uniqid(),
            'user_id' => $this->petugas->id,
            'siswa_id' => $siswa->id,
            'spp_id' => $tarif->id,
            'tgl_bayar' => '2026-11-05',
            'bulan_dibayar' => null,
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => $jumlah,
            'metode_pembayaran' => 'Tunai',
        ]);
    }
}
