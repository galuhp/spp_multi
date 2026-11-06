<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\LaporanTunggakan;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Laporan\LaporanSppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTunggakanTest extends TestCase
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

        $this->siswa = $this->buatSiswa('0071234561', 'Ahmad Rifai', $this->kelas, $this->tarif);
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_laporan_menghitung_bulan_jatuh_tempo_dan_mengabaikan_transaksi_void(): void
    {
        $this->bayar($this->siswa, 'Juli', 250000);
        $this->bayar($this->siswa, 'Agustus', 100000);
        $this->bayar($this->siswa, 'September', 250000, void: true);

        $laporan = app(LaporanSppService::class)->tunggakan([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'status' => 'semua',
        ]);

        $this->assertCount(1, $laporan['siswa']);
        $this->assertCount(5, $laporan['bulan']);

        $baris = $laporan['siswa']->first();

        $this->assertSame(1250000, $baris['tagihan']);
        $this->assertSame(350000, $baris['dibayar']);
        $this->assertSame(900000, $baris['tunggakan']);
        $this->assertSame(4, $baris['jumlah_bulan_tertunggak']);
        $this->assertSame('menunggak', $baris['status']);
        $this->assertContains('Agustus 2026', $baris['bulan_tertunggak']);
        $this->assertContains('September 2026', $baris['bulan_tertunggak']);
        $this->assertNotContains('Juli 2026', $baris['bulan_tertunggak']);
        $this->assertSame(28.0, $laporan['ringkasan']['persentase']);
    }

    public function test_tahun_ajaran_lampau_menghitung_dua_belas_bulan(): void
    {
        $tahunLalu = TahunAjaran::query()->create([
            'nama' => '2025/2026',
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
            'aktif' => false,
        ]);

        $bulan = app(LaporanSppService::class)->bulanJatuhTempo($tahunLalu);

        $this->assertCount(12, $bulan);
        $this->assertSame('Juli', $bulan[0]['bulan']);
        $this->assertSame('Juni', $bulan[11]['bulan']);
    }

    public function test_tunggakan_non_spp_dihitung_terpisah_per_pos(): void
    {
        $gedung = $this->buatTarifNonSpp(Spp::KATEGORI_UANG_GEDUNG, 'Uang Gedung', 2000000, '10');
        $seragam = $this->buatTarifNonSpp(Spp::KATEGORI_UANG_SERAGAM, 'Seragam', 500000);

        $this->bayarNonSpp($this->siswa, $gedung, 500000);
        $this->bayarNonSpp($this->siswa, $seragam, 500000, void: true);

        $laporan = app(LaporanSppService::class)->tunggakan([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'status' => 'semua',
        ]);

        $baris = $laporan['siswa']->first();

        $this->assertSame(1250000, $baris['tunggakan_spp']);
        $this->assertSame(2500000, $baris['tagihan_non_spp']);
        $this->assertSame(500000, $baris['dibayar_non_spp']);
        $this->assertSame(2000000, $baris['tunggakan_non_spp']);
        $this->assertSame(3250000, $baris['tunggakan']);
        $this->assertSame(2, $baris['jumlah_pos_non_spp']);
        $this->assertContains('Uang Gedung', $baris['pos_non_spp_tertunggak']);
        $this->assertContains('Seragam', $baris['pos_non_spp_tertunggak']);

        $this->assertCount(2, $laporan['per_kategori_non_spp']);

        $gedungRow = $laporan['per_kategori_non_spp']->firstWhere('kategori', Spp::KATEGORI_UANG_GEDUNG);
        $this->assertSame(2000000, $gedungRow['potensi']);
        $this->assertSame(500000, $gedungRow['realisasi']);
        $this->assertSame(1500000, $gedungRow['tunggakan']);

        $seragamRow = $laporan['per_kategori_non_spp']->firstWhere('kategori', Spp::KATEGORI_UANG_SERAGAM);
        $this->assertSame(500000, $seragamRow['tunggakan']);

        $this->actingAs($this->petugas)
            ->get(LaporanTunggakan::getUrl().'?tahun_ajaran_id='.$this->tahunAjaran->id.'&status=semua')
            ->assertOk()
            ->assertSee('Tunggakan Non-SPP')
            ->assertSee('Rp 2.000.000')
            ->assertSee('Uang Gedung');
    }

    public function test_tahun_ajaran_mendatang_belum_memiliki_bulan_jatuh_tempo(): void
    {
        $tahunMendatang = TahunAjaran::query()->create([
            'nama' => '2027/2028',
            'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2028-06-30',
            'aktif' => false,
        ]);

        $this->assertSame([], app(LaporanSppService::class)->bulanJatuhTempo($tahunMendatang));
    }

    public function test_siswa_tanpa_tarif_ditandai_tanpa_tarif(): void
    {
        $tarifLama = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'SPP Lama',
            'tahun' => 2025,
            'tahun_ajaran' => '2025/2026',
            'tingkat' => '11',
            'nominal' => 200000,
        ]);

        $kelasTanpaTarif = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => 'XI RPL 1',
            'tingkat' => '11',
        ]);

        $siswa = $this->buatSiswa('0071234564', 'Citra Lestari', $kelasTanpaTarif, $tarifLama);

        $laporan = app(LaporanSppService::class)->tunggakan([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'kelas_id' => $kelasTanpaTarif->id,
            'status' => 'semua',
        ]);

        $this->assertCount(1, $laporan['siswa']);
        $this->assertSame($siswa->id, $laporan['siswa']->first()['siswa']->id);
        $this->assertSame('tanpa_tarif', $laporan['siswa']->first()['status']);
        $this->assertSame(0, $laporan['siswa']->first()['tunggakan']);
    }

    public function test_filter_status_dan_kelas_membatasi_hasil(): void
    {
        $siswaLunas = $this->buatSiswa('0071234562', 'Siti Nurhaliza', $this->kelas, $this->tarif);

        foreach (Pembayaran::TAHUN_AJARAN_BULAN_LIST as $bulan) {
            $this->bayar($siswaLunas, $bulan, 250000);
        }

        $kelasLain = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => 'X RPL 2',
            'tingkat' => '10',
        ]);

        $siswaLain = $this->buatSiswa('0071234563', 'Bayu Pratama', $kelasLain, $this->tarif);

        $service = app(LaporanSppService::class);

        $menunggak = $service->tunggakan([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'kelas_id' => $this->kelas->id,
            'status' => 'menunggak',
        ]);

        $this->assertCount(1, $menunggak['siswa']);
        $this->assertSame($this->siswa->id, $menunggak['siswa']->first()['siswa']->id);

        $lunas = $service->tunggakan([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'kelas_id' => $this->kelas->id,
            'status' => 'lunas',
        ]);

        $this->assertCount(1, $lunas['siswa']);
        $this->assertSame($siswaLunas->id, $lunas['siswa']->first()['siswa']->id);

        $kelasLainHasil = $service->tunggakan([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'kelas_id' => $kelasLain->id,
            'status' => 'semua',
        ]);

        $this->assertCount(1, $kelasLainHasil['siswa']);
        $this->assertSame($siswaLain->id, $kelasLainHasil['siswa']->first()['siswa']->id);
    }

    public function test_halaman_dan_cetak_menampilkan_total_tunggakan(): void
    {
        $this->bayar($this->siswa, 'Juli', 250000);
        $this->bayar($this->siswa, 'Agustus', 100000);
        $this->bayar($this->siswa, 'September', 250000, void: true);

        $url = LaporanTunggakan::getUrl().'?tahun_ajaran_id='.$this->tahunAjaran->id;

        $this->actingAs($this->petugas)
            ->get($url)
            ->assertOk()
            ->assertSee('Laporan Tunggakan SPP')
            ->assertSee($this->siswa->nama)
            ->assertSee('Rp 900.000');

        $this->actingAs($this->petugas)
            ->get(route('laporan.tunggakan.cetak', [
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'status' => 'menunggak',
            ]))
            ->assertOk()
            ->assertSee('LAPORAN TUNGGAKAN SPP')
            ->assertSee($this->siswa->nama)
            ->assertSee('Rp 900.000');
    }

    public function test_siswa_tidak_dapat_mengakses_laporan_tunggakan(): void
    {
        $siswaUser = User::factory()->create(['role' => UserRole::Siswa]);

        $this->actingAs($siswaUser)
            ->get(LaporanTunggakan::getUrl())
            ->assertForbidden();

        $this->actingAs($siswaUser)
            ->get(route('laporan.tunggakan.cetak'))
            ->assertForbidden();
    }

    private function buatSiswa(string $nisn, string $nama, Kelas $kelas, Spp $tarif): Siswa
    {
        return Siswa::query()->create([
            'kelas_id' => $kelas->id,
            'spp_id' => $tarif->id,
            'nisn' => $nisn,
            'nis' => 'NIS'.$nisn,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
        ]);
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

    private function buatTarifNonSpp(string $kategori, string $nama, int $nominal, ?string $tingkat = null): Spp
    {
        return Spp::query()->create([
            'kategori' => $kategori,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => $nama,
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => $tingkat,
            'nominal' => $nominal,
        ]);
    }

    private function bayarNonSpp(Siswa $siswa, Spp $tarif, int $jumlah, bool $void = false): Pembayaran
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
            'void_at' => $void ? now() : null,
            'void_oleh' => $void ? $this->petugas->id : null,
            'void_alasan' => $void ? 'Koreksi transaksi' : null,
        ]);
    }
}
