<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\RekapPotensiPenerimaan;
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

class RekapPotensiPenerimaanTest extends TestCase
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

        $this->siswa = $this->buatSiswa('0071234561', 'Ahmad Rifai');
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_rekap_potensi_menghitung_potensi_realisasi_dan_tunggakan(): void
    {
        $this->buatSiswa('0071234562', 'Siti Nurhaliza');

        $this->bayar($this->siswa, 'Juli', 250000);
        $this->bayar($this->siswa, 'Agustus', 100000);

        $service = app(LaporanSppService::class);

        $perKelas = $service->rekapPotensi([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ], 'kelas');

        $this->assertCount(1, $perKelas['baris']);

        $baris = $perKelas['baris']->first();

        $this->assertSame('X RPL 1', $baris['label']);
        $this->assertSame(2, $baris['jumlah_siswa']);
        $this->assertSame(2, $baris['jumlah_menunggak']);
        $this->assertSame(2500000, $baris['tagihan']);
        $this->assertSame(350000, $baris['dibayar']);
        $this->assertSame(2150000, $baris['tunggakan']);
        $this->assertSame(14.0, $baris['persentase']);

        $perJurusan = $service->rekapPotensi([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ], 'jurusan');

        $this->assertSame('RPL — Rekayasa Perangkat Lunak', $perJurusan['baris']->first()['label']);

        $perTingkat = $service->rekapPotensi([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ], 'tingkat');

        $this->assertSame('Tingkat X', $perTingkat['baris']->first()['label']);
    }

    public function test_rekap_potensi_memasukkan_pos_non_spp(): void
    {
        $this->buatSiswa('0071234562', 'Siti Nurhaliza');

        $gedung = $this->buatTarifNonSpp(Spp::KATEGORI_UANG_GEDUNG, 'Uang Gedung', 2000000, '10');
        $this->buatTarifNonSpp(Spp::KATEGORI_UANG_SERAGAM, 'Seragam', 500000);

        $this->bayar($this->siswa, 'Juli', 250000);
        $this->bayar($this->siswa, 'Agustus', 100000);
        $this->bayarNonSpp($this->siswa, $gedung, 500000);

        $rekap = app(LaporanSppService::class)->rekapPotensi([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ], 'kelas');

        $baris = $rekap['baris']->first();

        $this->assertSame(2500000, $baris['tagihan_spp']);
        $this->assertSame(350000, $baris['dibayar_spp']);
        $this->assertSame(2150000, $baris['tunggakan_spp']);
        $this->assertSame(5000000, $baris['tagihan_non_spp']);
        $this->assertSame(500000, $baris['dibayar_non_spp']);
        $this->assertSame(4500000, $baris['tunggakan_non_spp']);
        $this->assertSame(7500000, $baris['tagihan']);
        $this->assertSame(850000, $baris['dibayar']);
        $this->assertSame(6650000, $baris['tunggakan']);
        $this->assertSame(11.3, $baris['persentase']);

        $this->assertCount(2, $rekap['per_kategori_non_spp']);

        $gedungRow = $rekap['per_kategori_non_spp']->firstWhere('kategori', Spp::KATEGORI_UANG_GEDUNG);
        $this->assertSame(4000000, $gedungRow['potensi']);
        $this->assertSame(500000, $gedungRow['realisasi']);
        $this->assertSame(3500000, $gedungRow['tunggakan']);

        $seragamRow = $rekap['per_kategori_non_spp']->firstWhere('kategori', Spp::KATEGORI_UANG_SERAGAM);
        $this->assertSame(1000000, $seragamRow['potensi']);
        $this->assertSame(1000000, $seragamRow['tunggakan']);

        $url = RekapPotensiPenerimaan::getUrl().'?tahun_ajaran_id='.$this->tahunAjaran->id.'&kelompok=kelas';

        $this->actingAs($this->petugas)
            ->get($url)
            ->assertOk()
            ->assertSee('Tunggakan Non-SPP')
            ->assertSee('Rp 7.500.000')
            ->assertSee('Rp 5.000.000')
            ->assertSee('Rp 4.500.000');

        $this->actingAs($this->petugas)
            ->get(route('laporan.potensi.cetak', [
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'kelompok' => 'kelas',
            ]))
            ->assertOk()
            ->assertSee('Termasuk Pos Non-SPP')
            ->assertSee('Rp 7.500.000')
            ->assertSee('Uang Gedung');
    }

    public function test_halaman_dan_cetak_rekap_menampilkan_potensi_dan_tunggakan(): void
    {
        $this->buatSiswa('0071234562', 'Siti Nurhaliza');

        $this->bayar($this->siswa, 'Juli', 250000);
        $this->bayar($this->siswa, 'Agustus', 100000);

        $url = RekapPotensiPenerimaan::getUrl().'?tahun_ajaran_id='.$this->tahunAjaran->id.'&kelompok=kelas';

        $this->actingAs($this->petugas)
            ->get($url)
            ->assertOk()
            ->assertSee('Rekap Penerimaan vs Potensi')
            ->assertSee('X RPL 1')
            ->assertSee('Rp 2.500.000')
            ->assertSee('Rp 2.150.000')
            ->assertSee('14,0%');

        $this->actingAs($this->petugas)
            ->get(route('laporan.potensi.cetak', [
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'kelompok' => 'kelas',
            ]))
            ->assertOk()
            ->assertSee('REKAP PENERIMAAN SPP VS POTENSI')
            ->assertSee('X RPL 1')
            ->assertSee('Rp 2.500.000')
            ->assertSee('Rp 2.150.000');
    }

    public function test_siswa_tidak_dapat_mengakses_rekap_potensi(): void
    {
        $siswaUser = User::factory()->create(['role' => UserRole::Siswa]);

        $this->actingAs($siswaUser)
            ->get(RekapPotensiPenerimaan::getUrl())
            ->assertForbidden();

        $this->actingAs($siswaUser)
            ->get(route('laporan.potensi.cetak'))
            ->assertForbidden();
    }

    private function buatSiswa(string $nisn, string $nama): Siswa
    {
        return Siswa::query()->create([
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->tarif->id,
            'nisn' => $nisn,
            'nis' => 'NIS'.$nisn,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
        ]);
    }

    private function bayar(Siswa $siswa, string $bulan, int $jumlah): Pembayaran
    {
        return Pembayaran::query()->create([
            'kode_transaksi' => 'TRX'.uniqid(),
            'user_id' => $this->petugas->id,
            'siswa_id' => $siswa->id,
            'spp_id' => $siswa->spp_id,
            'tgl_bayar' => '2026-11-05',
            'bulan_dibayar' => $bulan,
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => $jumlah,
            'metode_pembayaran' => 'Tunai',
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
