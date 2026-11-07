<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\KelulusanSiswa;
use App\Filament\Pages\KenaikanKelas;
use App\Filament\Pages\TagihanSiswa;
use App\Filament\Resources\Siswas\Pages\ListSiswas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\ProsesKelasBatch;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\KenaikanKelas\ProsesKelasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProsesKelasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Jurusan $jurusan;

    private TahunAjaran $asal;

    private TahunAjaran $tujuan;

    private Spp $spp;

    private ProsesKelasService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->jurusan = Jurusan::query()->create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->asal = TahunAjaran::query()->create([
            'nama' => '2027/2028',
            'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2028-06-30',
            'aktif' => true,
        ]);

        $this->tujuan = TahunAjaran::query()->create([
            'nama' => '2028/2029',
            'tanggal_mulai' => '2028-07-01',
            'tanggal_selesai' => '2029-06-30',
            'aktif' => false,
        ]);

        $this->spp = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'SPP',
            'tahun' => 2027,
            'tahun_ajaran' => '2027/2028',
            'nominal' => 250000,
        ]);

        $this->service = app(ProsesKelasService::class);
        $this->actingAs($this->admin);
    }

    public function test_naik_kelas_dua_kali_tidak_menggandakan_riwayat(): void
    {
        $kelasAsal = $this->buatKelas('10', $this->asal, 'X RPL 1');
        $kelasTujuan = $this->buatKelas('11', $this->tujuan, 'XI RPL 1');
        $siswa = $this->buatSiswa($kelasAsal);

        RiwayatKelas::query()->create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelasAsal->id,
            'tahun_ajaran_id' => $this->asal->id,
            'status' => RiwayatKelas::STATUS_AKTIF,
        ]);

        $keputusan = [
            $siswa->id => [
                'status' => RiwayatKelas::STATUS_NAIK,
                'kelas_tujuan_id' => $kelasTujuan->id,
            ],
        ];

        $pertama = $this->service->naikKelas(
            $this->asal->id,
            $this->tujuan->id,
            $kelasAsal->id,
            $keputusan,
            $this->admin->id,
        );

        $this->assertSame(1, $pertama['diproses']);
        $this->assertDatabaseHas('riwayat_kelas', [
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $this->tujuan->id,
            'status' => RiwayatKelas::STATUS_AKTIF,
        ]);
        $this->assertSame($kelasTujuan->id, $siswa->fresh()->kelas_id);

        $kedua = $this->service->naikKelas(
            $this->asal->id,
            $this->tujuan->id,
            $kelasAsal->id,
            $keputusan,
            $this->admin->id,
        );

        $this->assertSame(0, $kedua['diproses']);
        $this->assertNotEmpty($kedua['dilewati']);
        $this->assertSame(1, RiwayatKelas::query()
            ->where('siswa_id', $siswa->id)
            ->where('tahun_ajaran_id', $this->tujuan->id)
            ->count());
    }

    public function test_siswa_tingkat_akhir_tidak_bisa_naik_kelas(): void
    {
        $kelasAsal = $this->buatKelas('12', $this->asal, 'XII RPL 1');
        $kelasTujuan = $this->buatKelas('12', $this->tujuan, 'XII RPL 1');
        $siswa = $this->buatSiswa($kelasAsal);

        $hasil = $this->service->naikKelas(
            $this->asal->id,
            $this->tujuan->id,
            $kelasAsal->id,
            [
                $siswa->id => [
                    'status' => RiwayatKelas::STATUS_NAIK,
                    'kelas_tujuan_id' => $kelasTujuan->id,
                ],
            ],
            $this->admin->id,
        );

        $this->assertSame(0, $hasil['diproses']);
        $this->assertNotEmpty($hasil['pesan']);
        $this->assertDatabaseMissing('riwayat_kelas', [
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $this->tujuan->id,
        ]);
    }

    public function test_kelulusan_dengan_tunggakan_tetap_diarsipkan_dan_tunggakan_tercatat(): void
    {
        $kelas = $this->buatKelas('12', $this->asal, 'XII RPL 1');
        $siswa = $this->buatSiswa($kelas);

        RiwayatKelas::query()->create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelas->id,
            'tahun_ajaran_id' => $this->asal->id,
            'status' => RiwayatKelas::STATUS_AKTIF,
        ]);

        $tunggakan = $this->service->tunggakan($siswa->fresh());
        $this->assertSame(12, $tunggakan['jumlah_bulan']);
        $this->assertSame(3000000, $tunggakan['nominal']);

        $hasil = $this->service->luluskan(
            $this->asal->id,
            $kelas->id,
            [$siswa->id],
            '2028-06-20',
            $this->admin->id,
        );

        $this->assertSame(1, $hasil['diproses']);

        $siswa->refresh();
        $this->assertTrue($siswa->is_archived);
        $this->assertSame('lulus', $siswa->status);
        $this->assertSame('2028-06-20', $siswa->tanggal_lulus?->format('Y-m-d'));
        $this->assertNotNull($siswa->archived_at);

        $this->assertDatabaseHas('riwayat_kelas', [
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $this->asal->id,
            'status' => RiwayatKelas::STATUS_LULUS,
        ]);

        $this->assertDatabaseHas('proses_kelas_batch', [
            'id' => $hasil['batch']?->id,
            'jenis' => ProsesKelasBatch::JENIS_KELULUSAN,
            'jumlah_siswa' => 1,
        ]);
    }

    public function test_loket_menyembunyikan_arsip_dan_menampilkannya_dengan_opsi_sertakan_arsip(): void
    {
        $kelas = $this->buatKelas('12', $this->asal, 'XII RPL 1');

        $aktif = $this->buatSiswa($kelas, ['nama' => 'Siswa Aktif', 'nisn' => '1111111111', 'nis' => '11111111']);
        $arsip = $this->buatSiswa($kelas, [
            'nama' => 'Alumni Arsip',
            'nisn' => '2222222222',
            'nis' => '22222222',
            'status' => 'lulus',
            'is_archived' => true,
            'archived_at' => now(),
            'tanggal_lulus' => '2028-06-20',
        ]);

        $this->followingRedirects()
            ->get(route('pembayaran.create', ['kelas_id' => $kelas->id]))
            ->assertOk()
            ->assertSee($aktif->nama)
            ->assertDontSee($arsip->nama);

        $this->followingRedirects()
            ->get(route('pembayaran.create', ['kelas_id' => $kelas->id, 'sertakan_arsip' => 1]))
            ->assertOk()
            ->assertSee($arsip->nama);
    }

    public function test_daftar_siswa_default_menyembunyikan_arsip(): void
    {
        $kelas = $this->buatKelas('12', $this->asal, 'XII RPL 1');

        $aktif = $this->buatSiswa($kelas, ['nama' => 'Siswa Aktif', 'nisn' => '3333333333', 'nis' => '33333333']);
        $arsip = $this->buatSiswa($kelas, [
            'nama' => 'Alumni Arsip',
            'nisn' => '4444444444',
            'nis' => '44444444',
            'status' => 'lulus',
            'is_archived' => true,
            'archived_at' => now(),
            'tanggal_lulus' => '2028-06-20',
        ]);

        Livewire::test(ListSiswas::class)
            ->assertCanSeeTableRecords([$aktif])
            ->assertCanNotSeeTableRecords([$arsip]);
    }

    public function test_halaman_kenaikan_dan_kelulusan_dapat_dibuka_admin(): void
    {
        $this->get(KenaikanKelas::getUrl())->assertOk();
        $this->get(KelulusanSiswa::getUrl())->assertOk();
    }

    public function test_tahun_ajaran_tujuan_tidak_boleh_sama_dengan_tahun_ajaran_asal(): void
    {
        Livewire::test(KenaikanKelas::class)
            ->set('tahun_ajaran_asal_id', $this->asal->id)
            ->assertSet('tahun_ajaran_tujuan_id', $this->tujuan->id)
            ->set('tahun_ajaran_tujuan_id', $this->asal->id)
            ->assertSet('tahun_ajaran_tujuan_id', null)
            ->assertSet('galat', 'Tahun ajaran tujuan tidak boleh sama dengan tahun ajaran asal.');
    }

    public function test_opsi_tahun_ajaran_tujuan_tidak_memuat_tahun_ajaran_asal(): void
    {
        $component = Livewire::test(KenaikanKelas::class)
            ->set('tahun_ajaran_asal_id', $this->asal->id);

        $options = $component->instance()->getTahunAjaranTujuanOptionsProperty();

        $this->assertArrayNotHasKey($this->asal->id, $options);
        $this->assertArrayHasKey($this->tujuan->id, $options);
    }

    public function test_tagihan_siswa_mendukung_pencarian_nama_dan_filter_kelas(): void
    {
        $kelas = $this->buatKelas('12', $this->asal, 'XII RPL 1');

        $siswa = $this->buatSiswa($kelas, [
            'nama' => 'Budi Santoso',
            'nisn' => '5555555555',
            'nis' => '55555555',
        ]);

        $this->buatSiswa($kelas, [
            'nama' => 'Alumni Arsip',
            'nisn' => '6666666666',
            'nis' => '66666666',
            'status' => 'lulus',
            'is_archived' => true,
            'archived_at' => now(),
            'tanggal_lulus' => '2028-06-20',
        ]);

        Livewire::test(TagihanSiswa::class)
            ->assertSet('cara_pilih', 'cari')
            ->set('cari', 'Budi')
            ->assertSee('Budi Santoso')
            ->set('cari', 'Alumni')
            ->assertDontSee('Alumni Arsip')
            ->set('cara_pilih', 'kelas')
            ->set('kelas_id', $kelas->id)
            ->assertSet('selectedSiswaId', $siswa->id)
            ->assertSee('Budi Santoso');
    }

    public function test_petugas_dapat_menjalankan_kenaikan_kelas(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);
        $this->actingAs($petugas);

        $kelasAsal = $this->buatKelas('10', $this->asal, 'X RPL 1');
        $kelasTujuan = $this->buatKelas('11', $this->tujuan, 'XI RPL 1');
        $siswa = $this->buatSiswa($kelasAsal);

        $hasil = $this->service->naikKelas(
            $this->asal->id,
            $this->tujuan->id,
            $kelasAsal->id,
            [
                $siswa->id => [
                    'status' => RiwayatKelas::STATUS_NAIK,
                    'kelas_tujuan_id' => $kelasTujuan->id,
                ],
            ],
            $petugas->id,
        );

        $this->assertSame(1, $hasil['diproses']);
    }

    public function test_batalkan_batch_ditolak_jika_sudah_ada_pembayaran(): void
    {
        $kelasAsal = $this->buatKelas('10', $this->asal, 'X RPL 1');
        $kelasTujuan = $this->buatKelas('11', $this->tujuan, 'XI RPL 1');
        $siswa = $this->buatSiswa($kelasAsal);

        RiwayatKelas::query()->create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelasAsal->id,
            'tahun_ajaran_id' => $this->asal->id,
            'status' => RiwayatKelas::STATUS_AKTIF,
        ]);

        $hasil = $this->service->naikKelas(
            $this->asal->id,
            $this->tujuan->id,
            $kelasAsal->id,
            [
                $siswa->id => [
                    'status' => RiwayatKelas::STATUS_NAIK,
                    'kelas_tujuan_id' => $kelasTujuan->id,
                ],
            ],
            $this->admin->id,
        );

        Pembayaran::query()->create([
            'kode_transaksi' => 'SPP-TEST-0001',
            'user_id' => $this->admin->id,
            'siswa_id' => $siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2028-07-10',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2028,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $batal = $this->service->batalkan($hasil['batch']);

        $this->assertFalse($batal['berhasil']);
        $this->assertNotNull($batal['alasan']);
        $this->assertSame(ProsesKelasBatch::STATUS_SELESAI, $hasil['batch']->fresh()->status);
    }

    public function test_batch_kenaikan_kelas_otomatis_mengisi_tujuan_dan_mendukung_override_lintas_kelas(): void
    {
        // Kasus SMK: X RPL 1 & X RPL 2 -> otomatis memilih XI RPL 1 untuk kelas asal X RPL 1
        $kelasAsalSmk = $this->buatKelas('10', $this->asal, 'X RPL 1');
        $kelasTujuanSmk1 = $this->buatKelas('11', $this->tujuan, 'XI RPL 1');
        $kelasTujuanSmk2 = $this->buatKelas('11', $this->tujuan, 'XI RPL 2');

        $siswaA = $this->buatSiswa($kelasAsalSmk, ['nama' => 'Andi', 'nisn' => '1000000001', 'nis' => '10000001']);
        $siswaB = $this->buatSiswa($kelasAsalSmk, ['nama' => 'Budi', 'nisn' => '1000000002', 'nis' => '10000002']);

        $component = Livewire::test(KenaikanKelas::class)
            ->set('tahun_ajaran_asal_id', $this->asal->id)
            ->set('tahun_ajaran_tujuan_id', $this->tujuan->id)
            ->set('kelas_asal_id', $kelasAsalSmk->id)
            ->assertSet('kelas_tujuan_batch_id', $kelasTujuanSmk1->id)
            ->assertSet("keputusan.{$siswaA->id}.kelas_tujuan_id", $kelasTujuanSmk1->id)
            ->assertSet("keputusan.{$siswaB->id}.kelas_tujuan_id", $kelasTujuanSmk1->id);

        // Ubah Kelas Tujuan Batch ke XI RPL 2 -> semua siswa naik otomatis ikut berubah ke XI RPL 2
        $component->set('kelas_tujuan_batch_id', $kelasTujuanSmk2->id)
            ->assertSet("keputusan.{$siswaA->id}.kelas_tujuan_id", $kelasTujuanSmk2->id)
            ->assertSet("keputusan.{$siswaB->id}.kelas_tujuan_id", $kelasTujuanSmk2->id);

        // User tetap bisa manual pilih kelas tujuan berbeda untuk 1 siswa (misal Siswa B pindah ke XI RPL 1)
        $component->set("keputusan.{$siswaB->id}.kelas_tujuan_id", $kelasTujuanSmk1->id)
            ->call('pratinjau')
            ->assertSet('pratinjau', true)
            ->call('eksekusi');

        $this->assertSame($kelasTujuanSmk2->id, $siswaA->fresh()->kelas_id);
        $this->assertSame($kelasTujuanSmk1->id, $siswaB->fresh()->kelas_id);
    }

    public function test_kenaikan_kelas_sd_lintas_nama_kelas_dan_tingkat(): void
    {
        $jurusanUmum = Jurusan::query()->create(['kode' => 'UMUM', 'nama' => 'Umum']);

        $kelas1A = Kelas::query()->create([
            'tahun_ajaran_id' => $this->asal->id,
            'jurusan_id' => $jurusanUmum->id,
            'nama_kelas' => 'Kelas 1A',
            'tingkat' => '1',
        ]);

        $kelas2B = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tujuan->id,
            'jurusan_id' => $this->jurusan->id, // bahkan jika berbeda/lintas pengelompokan
            'nama_kelas' => 'Kelas 2B - Unggulan',
            'tingkat' => '2',
        ]);

        $siswaSd = $this->buatSiswa($kelas1A, ['nama' => 'Citra SD', 'nisn' => '2000000001', 'nis' => '20000001']);

        Livewire::test(KenaikanKelas::class)
            ->set('tahun_ajaran_asal_id', $this->asal->id)
            ->set('tahun_ajaran_tujuan_id', $this->tujuan->id)
            ->set('kelas_asal_id', $kelas1A->id)
            ->assertSet('kelas_tujuan_batch_id', $kelas2B->id)
            ->assertSet("keputusan.{$siswaSd->id}.kelas_tujuan_id", $kelas2B->id)
            ->call('pratinjau')
            ->call('eksekusi');

        $this->assertSame($kelas2B->id, $siswaSd->fresh()->kelas_id);
    }

    private function buatKelas(string $tingkat, TahunAjaran $tahunAjaran, string $nama): Kelas
    {
        return Kelas::query()->create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => $nama,
            'tingkat' => $tingkat,
        ]);
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatSiswa(Kelas $kelas, array $atribut = []): Siswa
    {
        return Siswa::query()->create(array_merge([
            'kelas_id' => $kelas->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0012345678',
            'nis' => '12345678',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ], $atribut));
    }
}
