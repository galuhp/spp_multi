<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\SiswaSubsidi;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Laporan\LaporanSppService;
use App\Support\Pembayaran\PembayaranService;
use App\Support\Pembayaran\SubsidiSiswaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaSubsidiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TahunAjaran $tahunAjaran;

    private Kelas $kelas;

    private Spp $spp;

    private Spp $uangGedung;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($this->admin);

        $this->tahunAjaran = TahunAjaran::query()->firstOrCreate(
            ['nama' => '2026/2027'],
            [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'aktif' => true,
            ],
        );

        $jurusan = Jurusan::query()->firstOrCreate(
            ['kode' => 'RPL'],
            ['nama' => 'Rekayasa Perangkat Lunak'],
        );

        $this->kelas = Kelas::query()->firstOrCreate(
            [
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'nama_kelas' => 'X RPL 1',
            ],
            [
                'jurusan_id' => $jurusan->id,
                'tingkat' => '10',
            ],
        );

        $this->spp = Spp::query()->updateOrCreate(
            [
                'kategori' => Spp::KATEGORI_SPP,
                'tahun_ajaran' => '2026/2027',
                'tingkat' => '10',
            ],
            [
                'tipe' => Spp::TIPE_BULANAN,
                'tahun' => 2026,
                'nominal' => 250000,
            ],
        );

        $this->uangGedung = Spp::query()->updateOrCreate(
            [
                'kategori' => Spp::KATEGORI_UANG_GEDUNG,
                'tahun_ajaran' => '2026/2027',
                'tingkat' => '10',
            ],
            [
                'tipe' => Spp::TIPE_SEKALI_BAYAR,
                'nama' => 'Uang Gedung',
                'tahun' => 2026,
                'nominal' => 1000000,
            ],
        );

        $this->siswa = Siswa::query()->create([
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0081234567',
            'nis' => '2026099',
            'nama' => 'Budi Pratama',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
            'tanggal_masuk' => '2026-07-01',
        ]);
    }

    public function test_subsidi_persentase_mengurangi_kewajiban_spp_dan_pos(): void
    {
        $service = app(SubsidiSiswaService::class);

        // 50% discount on SPP
        SiswaSubsidi::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaSubsidi::CAKUPAN_SPP,
            'persentase' => 50,
            'jenis' => SiswaSubsidi::JENIS_ANAK_GURU,
            'is_aktif' => true,
        ]);

        // 25% discount on Uang Gedung
        SiswaSubsidi::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaSubsidi::CAKUPAN_POS,
            'spp_id' => $this->uangGedung->id,
            'persentase' => 25,
            'jenis' => SiswaSubsidi::JENIS_PRESTASI,
            'is_aktif' => true,
        ]);

        $this->assertSame(125000, $service->kewajibanSiswa($this->siswa, $this->spp));
        $this->assertSame(125000, $service->potonganSiswa($this->siswa, $this->spp));
        $this->assertSame(50, $service->persentaseSiswa($this->siswa, $this->spp));

        $this->assertSame(750000, $service->kewajibanSiswa($this->siswa, $this->uangGedung));
        $this->assertSame(250000, $service->potonganSiswa($this->siswa, $this->uangGedung));
        $this->assertSame(25, $service->persentaseSiswa($this->siswa, $this->uangGedung));
    }

    public function test_tagihan_terbuka_menampilkan_wajib_bayar_dan_potongan(): void
    {
        SiswaSubsidi::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaSubsidi::CAKUPAN_SPP,
            'persentase' => 40,
            'jenis' => SiswaSubsidi::JENIS_AFIRMASI,
            'is_aktif' => true,
        ]);

        $items = collect(app(PembayaranService::class)->tagihanTerbuka($this->siswa));

        $sppJuli = $items->first(fn (array $item): bool => $item['tipe'] === 'spp' && $item['bulan'] === 'Juli');

        $this->assertNotNull($sppJuli);
        $this->assertSame(150000, $sppJuli['nominal']);
        $this->assertSame(250000, $sppJuli['nominal_asli']);
        $this->assertSame(100000, $sppJuli['potongan']);
        $this->assertSame(40, $sppJuli['persen_subsidi']);
    }

    public function test_pembayaran_sebesar_wajib_bayar_menjadikan_lunas(): void
    {
        SiswaSubsidi::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaSubsidi::CAKUPAN_SPP,
            'persentase' => 50,
            'is_aktif' => true,
        ]);

        Pembayaran::query()->create([
            'kode_transaksi' => 'TRX-TEST-001',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-07-05',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 125000,
            'metode_pembayaran' => 'tunai',
        ]);

        $items = collect(app(PembayaranService::class)->tagihanTerbuka($this->siswa));

        $sppJuli = $items->first(fn (array $item): bool => $item['tipe'] === 'spp' && $item['bulan'] === 'Juli');
        $this->assertNull($sppJuli, 'Bulan Juli seharusnya sudah lunas karena dibayar sesuai kewajiban subsidi.');
    }

    public function test_laporan_tunggakan_menghitung_berdasarkan_kewajiban_subsidi(): void
    {
        SiswaSubsidi::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaSubsidi::CAKUPAN_SPP,
            'persentase' => 50,
            'is_aktif' => true,
        ]);

        $laporan = app(LaporanSppService::class)->tunggakan([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'kelas_id' => $this->kelas->id,
        ]);

        $barisSiswa = collect($laporan['siswa'])->firstWhere('siswa.id', $this->siswa->id);

        $this->assertNotNull($barisSiswa);
        // SPP per month is 125.000 instead of 250.000
        $this->assertGreaterThan(0, $barisSiswa['tagihan_spp']);
        $this->assertSame(0, $barisSiswa['tagihan_spp'] % 125000);
    }

    public function test_pembaca_nilai_persentase_berbagai_format(): void
    {
        $this->assertSame(50, \App\Support\ImporSiswa\PembacaNilai::persentase(50));
        $this->assertSame(50, \App\Support\ImporSiswa\PembacaNilai::persentase(0.5));
        $this->assertSame(25, \App\Support\ImporSiswa\PembacaNilai::persentase('25%'));
        $this->assertSame(75, \App\Support\ImporSiswa\PembacaNilai::persentase(' 75 % '));
        $this->assertSame(100, \App\Support\ImporSiswa\PembacaNilai::persentase('100%'));
        $this->assertNull(\App\Support\ImporSiswa\PembacaNilai::persentase(0));
        $this->assertNull(\App\Support\ImporSiswa\PembacaNilai::persentase(null));
        $this->assertNull(\App\Support\ImporSiswa\PembacaNilai::persentase(''));
    }

    public function test_impor_siswa_otomatis_mencatat_subsidi(): void
    {
        $batchId = (string) \Illuminate\Support\Str::uuid();
        $staging = \App\Models\ImportSiswaStaging::query()->create([
            'batch_id' => $batchId,
            'user_id' => $this->admin->id,
            'nama_file' => 'test.xlsx',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'spp_id' => $this->spp->id,
            'baris' => 2,
            'nisn' => '0098765432',
            'nik' => '3201010101010001',
            'nis' => '2026199',
            'nama_lengkap' => 'Siti Aisyah',
            'jenis_kelamin' => 'P',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2010-05-10',
            'agama' => 'Islam',
            'alamat' => 'Jl. Merdeka No. 10',
            'tanggal_masuk' => '2026-07-01',
            'kode_kompetensi' => 'RPL',
            'kelas' => 'X RPL 1',
            'nama_ayah' => 'Ahmad',
            'hp_wali' => '081234567890',
            'potongan' => 30,
            'status' => \App\Enums\StatusBarisImpor::Valid,
        ]);

        $service = app(\App\Support\ImporSiswa\ImporSiswaService::class);
        $service->simpanBatch($batchId, false);

        $siswaBaru = Siswa::query()->where('nis', '2026199')->first();
        $this->assertNotNull($siswaBaru);

        $subsidi = SiswaSubsidi::query()->where('siswa_id', $siswaBaru->id)->first();
        $this->assertNotNull($subsidi);
        $this->assertSame(30, $subsidi->persentase);
        $this->assertSame(SiswaSubsidi::CAKUPAN_SPP, $subsidi->cakupan);
        $this->assertTrue($subsidi->is_aktif);
    }

    public function test_kasir_data_mencakup_diskon_dan_pembayaran_subsidi(): void
    {
        SiswaSubsidi::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaSubsidi::CAKUPAN_SPP,
            'persentase' => 40,
            'is_aktif' => true,
        ]);

        $kasir = new \App\Filament\Pages\EntriPembayaranKasir;
        $kasir->pilihSiswa($this->siswa->id);
        $data = $kasir->kasirData;

        $this->assertNotEmpty($data['months']);
        $bulanJuli = collect($data['months'])->firstWhere('bulan', 'Juli');
        $this->assertNotNull($bulanJuli);
        $this->assertSame(40, $bulanJuli['diskon']);

        // Check Pembayaran::subsidi() helper
        $pembayaran = Pembayaran::query()->create([
            'kode_transaksi' => 'TRX-TEST-DISCOUNT',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => now()->toDateString(),
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 150000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $this->assertNotNull($pembayaran->subsidi());
        $this->assertSame(40, $pembayaran->subsidi()->persentase);

        $response = $this->get(route('laporan.cetak', [
            'tgl_awal' => now()->toDateString(),
            'tgl_akhir' => now()->toDateString(),
        ]));
        $response->assertOk();
        $response->assertSee('Diskon 40%');

        $cetakResponse = $this->get(route('pembayaran.cetak', $pembayaran));
        $cetakResponse->assertOk();
        $cetakResponse->assertSee('Waktu Transaksi:');
        $cetakResponse->assertSee('Potongan subsidi/diskon 40%');
    }
}

