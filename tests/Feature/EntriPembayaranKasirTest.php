<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\EntriPembayaranKasir;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Pembayaran\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EntriPembayaranKasirTest extends TestCase
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

        $jurusan = Jurusan::query()->create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->kelas = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $jurusan->id,
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
        ]);

        $this->spp = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 250000,
        ]);

        $this->uangGedung = Spp::query()->create([
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Uang Gedung',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'nominal' => 1000000,
        ]);

        $this->siswa = Siswa::query()->create([
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0071234561',
            'nis' => '2026001',
            'nama' => 'Ahmad Rifai',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
    }

    public function test_halaman_kasir_dapat_dibuka(): void
    {
        Livewire::test(EntriPembayaranKasir::class)
            ->assertOk()
            ->assertSee('Pilih Siswa');
    }

    public function test_filter_tingkat_kelas_menyaring_hasil_pencarian_siswa(): void
    {
        $kelasXI = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $this->kelas->jurusan_id,
            'nama_kelas' => 'XI RPL 1',
            'tingkat' => '11',
        ]);

        $sppXI = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '11',
            'nominal' => 300000,
        ]);

        Siswa::query()->create([
            'kelas_id' => $kelasXI->id,
            'spp_id' => $sppXI->id,
            'nisn' => '0071234562',
            'nis' => '2026002',
            'nama' => 'Budi Tingkat XI',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        Livewire::test(EntriPembayaranKasir::class)
            ->assertSee('Tingkat Kelas')
            ->assertSee('Semua')
            ->set('cari', '007')
            ->assertSee('Ahmad Rifai')
            ->assertSee('Budi Tingkat XI')
            ->call('pilihTingkat', '10')
            ->assertSet('tingkat', '10')
            ->assertSet('cari', '')
            ->set('cari', '007')
            ->assertSee('Ahmad Rifai')
            ->assertDontSee('Budi Tingkat XI')
            ->call('pilihTingkat', '11')
            ->assertSet('cari', '')
            ->set('cari', '007')
            ->assertSee('Budi Tingkat XI')
            ->assertDontSee('Ahmad Rifai')
            ->call('pilihTingkat', 'semua')
            ->set('cari', '007')
            ->assertSee('Ahmad Rifai')
            ->assertSee('Budi Tingkat XI');
    }

    public function test_data_kasir_menampilkan_bulan_dan_biaya_lain_sesuai_tingkat(): void
    {
        $sppLain = Spp::query()->create([
            'kategori' => Spp::KATEGORI_BIAYA_PRAKTIK,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Praktik Tingkat XI',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '11',
            'nominal' => 300000,
        ]);

        $praktikX = Spp::query()->create([
            'kategori' => Spp::KATEGORI_BIAYA_UJIAN,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Ujian Tingkat X',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 200000,
        ]);

        $component = Livewire::test(EntriPembayaranKasir::class)
            ->call('pilihSiswa', $this->siswa->id);

        $data = $component->instance()->kasirData;

        $this->assertSame(250000, $data['tarif']);
        $this->assertCount(12, $data['months']);
        $this->assertCount(2, $data['non_spp']);
        $this->assertEqualsCanonicalizing(
            ["spp:{$this->uangGedung->id}", "spp:{$praktikX->id}"],
            collect($data['non_spp'])->pluck('key')->all(),
        );
        $this->assertNotContains("spp:{$sppLain->id}", collect($data['non_spp'])->pluck('key')->all());
    }

    public function test_tarif_dipisahkan_antara_tab_biaya_bulanan_dan_biaya_sekali_bayar(): void
    {
        $tabungan = Spp::query()->create([
            'kategori' => 'Tabungan',
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'Tabungan Bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 50000,
        ]);

        $component = Livewire::test(EntriPembayaranKasir::class)
            ->call('pilihSiswa', $this->siswa->id);

        // Tab Biaya Sekali Bayar hanya berisi tarif sekali_bayar (tidak berisi Tabungan Bulanan)
        $nonSpp = collect($component->instance()->kasirData['non_spp']);
        $this->assertSame(["spp:{$this->uangGedung->id}"], $nonSpp->pluck('key')->all());

        // Tab Biaya Bulanan (monthly_groups) menampilkan SPP dan Tabungan Bulanan (12 bulan masing-masing)
        $monthlyGroups = collect($component->instance()->kasirData['monthly_groups']);
        $this->assertCount(2, $monthlyGroups);
        $this->assertSame($this->spp->id, $monthlyGroups[0]['id']);
        $this->assertSame($tabungan->id, $monthlyGroups[1]['id']);
        $this->assertCount(12, $monthlyGroups[1]['months']);
        $this->assertSame(50000, $monthlyGroups[1]['months'][0]['nominal']);
    }

    public function test_tarif_bulanan_kedua_dengan_kategori_spp_tetap_memakai_nominal_dan_status_sendiri(): void
    {
        $tabunganSpp = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'Tabungan per bulan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 35000,
        ]);

        // Bayar SPP utama bulan Juli (Rp 250.000), sedangkan Tabungan per bulan belum dibayar
        Pembayaran::query()->create([
            'kode_transaksi' => 'SPP-JULI-UTAMA',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-07-10',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $component = Livewire::test(EntriPembayaranKasir::class)
            ->call('pilihSiswa', $this->siswa->id);

        $monthlyGroups = collect($component->instance()->kasirData['monthly_groups'])->keyBy('id');

        $grupSpp = $monthlyGroups->get($this->spp->id);
        $grupTabungan = $monthlyGroups->get($tabunganSpp->id);

        $this->assertNotNull($grupSpp);
        $this->assertNotNull($grupTabungan);

        // Juli pada SPP utama harus lunas, sedangkan Juli pada Tabungan per bulan tetap 35.000 dan belum lunas
        $this->assertSame('lunas', $grupSpp['months'][0]['status']);
        $this->assertNotSame('lunas', $grupTabungan['months'][0]['status']);
        $this->assertSame(35000, $grupTabungan['months'][0]['nominal']);

        // Uraian kuitansi (namaPos) membedakan SPP dan Tabungan per bulan
        $pembayaranTabungan = Pembayaran::query()->create([
            'kode_transaksi' => 'TAB-JULI-2026',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $tabunganSpp->id,
            'tgl_bayar' => '2026-07-10',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 35000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $this->assertSame('Tabungan per bulan Bulan Juli 2026', $pembayaranTabungan->namaPos());
    }

    public function test_kasir_dapat_menyimpan_spp_dan_biaya_lain_dalam_satu_kuitansi(): void
    {
        $items = collect(app(PembayaranService::class)->tagihanTerbuka($this->siswa))->keyBy('key');
        $bulanKey = "spp:{$this->spp->id}:Juli:2026";
        $biayaKey = "spp:{$this->uangGedung->id}";

        $this->assertTrue($items->has($bulanKey));
        $this->assertTrue($items->has($biayaKey));

        Livewire::test(EntriPembayaranKasir::class)
            ->call('pilihSiswa', $this->siswa->id)
            ->call('simpan', [$bulanKey], [$biayaKey], 'Tunai', '2026-07-10', 'Kasir')
            ->assertRedirectContains('/pembayaran/');

        $rows = Pembayaran::query()
            ->where('siswa_id', $this->siswa->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $rows);
        $this->assertSame($rows[0]->kode_transaksi, $rows[1]->kode_transaksi);
        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswa->id,
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
        ]);
        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangGedung->id,
            'bulan_dibayar' => null,
            'jumlah_bayar' => 1000000,
        ]);
    }

    public function test_kasir_menolak_biaya_lain_tingkat_lain(): void
    {
        $praktikXI = Spp::query()->create([
            'kategori' => Spp::KATEGORI_BIAYA_PRAKTIK,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Praktik Tingkat XI',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '11',
            'nominal' => 300000,
        ]);

        Livewire::test(EntriPembayaranKasir::class)
            ->call('pilihSiswa', $this->siswa->id)
            ->call('simpan', [], ["spp:{$praktikXI->id}"], 'Tunai', '2026-07-10');

        $this->assertSame(0, Pembayaran::query()->count());
    }
}
