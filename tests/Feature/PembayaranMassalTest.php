<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\PembayaranGabungans\Pages\CreatePembayaranGabungan;
use App\Filament\Resources\PembayaranGabungans\Pages\EditPembayaranGabungan;
use App\Filament\Resources\PembayaranGabungans\PembayaranGabunganResource;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\PembayaranGabunganBaris;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Pembayaran\PembayaranService;
use App\Support\PembayaranMassal\PembayaranMassalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PembayaranMassalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Jurusan $jurusan;

    private TahunAjaran $tahunAjaran;

    private Kelas $kelas;

    private Spp $spp;

    private Siswa $siswa;

    private PembayaranMassalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($this->admin);

        $this->jurusan = Jurusan::query()->create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak']);

        $this->tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2027/2028',
            'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2028-06-30',
            'aktif' => true,
        ]);

        $this->kelas = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
        ]);

        $this->spp = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'SPP',
            'tahun' => 2027,
            'tahun_ajaran' => '2027/2028',
            'nominal' => 250000,
        ]);

        $this->siswa = $this->buatSiswa();

        $this->service = app(PembayaranMassalService::class);
    }

    public function test_posting_dua_kali_dengan_token_sama_tidak_menggandakan_pembayaran(): void
    {
        $gabungan = $this->buatGabungan();
        $this->tambahBaris($gabungan, $this->siswa, 250000);
        $this->tetapkanTotal($gabungan, 250000);

        $pertama = $this->service->posting($gabungan, $this->admin->id);
        $kedua = $this->service->posting($gabungan->fresh(), $this->admin->id);

        $this->assertTrue($pertama['berhasil']);
        $this->assertTrue($kedua['berhasil']);
        $this->assertSame(1, Pembayaran::query()->where('siswa_id', $this->siswa->id)->count());
        $this->assertSame(PembayaranGabungan::STATUS_DIPOSTING, $gabungan->fresh()->status);
    }

    public function test_selisih_total_menonaktifkan_posting(): void
    {
        $gabungan = $this->buatGabungan();
        $this->tambahBaris($gabungan, $this->siswa, 250000);
        $this->tetapkanTotal($gabungan, 200000);

        $cek = $this->service->cekPosting($gabungan->fresh());

        $this->assertFalse($cek['boleh']);
        $this->assertSame(-50000, $cek['selisih']);

        $hasil = $this->service->posting($gabungan, $this->admin->id);

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame(0, Pembayaran::query()->count());
    }

    public function test_kelebihan_bayar_ditolak(): void
    {
        $validasi = app(PembayaranService::class)->validasiBaris($this->siswa, 4000000);

        $this->assertSame('error', $validasi['status']);
        $this->assertStringContainsString('melebihi total sisa', (string) $validasi['pesan']);
    }

    public function test_siswa_duplikat_diberi_peringatan(): void
    {
        $gabungan = $this->buatGabungan();
        $this->tambahBaris($gabungan, $this->siswa, 250000);
        $kedua = $this->tambahBaris($gabungan, $this->siswa, 250000);

        $this->assertSame(PembayaranGabunganBaris::STATUS_PERINGATAN, $kedua->status_validasi);
        $this->assertStringContainsString('lebih dari sekali', (string) $kedua->pesan);
    }

    public function test_baris_tidak_valid_saat_posting_membatalkan_seluruh_posting(): void
    {
        $siswaLain = $this->buatSiswa(['nama' => 'Siti Aminah', 'nisn' => '0098765432', 'nis' => '00987654']);

        $gabungan = $this->buatGabungan();
        $this->tambahBaris($gabungan, $this->siswa, 3000000);
        $this->tambahBaris($gabungan, $siswaLain, 250000);
        $this->tetapkanTotal($gabungan, 3250000);

        // Pembayaran pihak lain menghabiskan satu bulan sebelum posting.
        Pembayaran::query()->create([
            'kode_transaksi' => 'SPP-TEST-EXTERNAL',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => now()->format('Y-m-d'),
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2027,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $hasil = $this->service->posting($gabungan, $this->admin->id);

        $this->assertFalse($hasil['berhasil']);
        $this->assertNotNull($hasil['baris_error_id']);
        $this->assertSame(PembayaranGabungan::STATUS_DRAFT, $gabungan->fresh()->status);

        // Hanya pembayaran eksternal yang tersisa.
        $this->assertSame(1, Pembayaran::query()->count());
        $this->assertDatabaseHas('pembayaran_gabungan_baris', [
            'id' => $hasil['baris_error_id'],
            'status_validasi' => PembayaranGabunganBaris::STATUS_ERROR,
        ]);
    }

    public function test_dua_gabungan_untuk_tagihan_sama_hanya_satu_berhasil(): void
    {
        $tagihan = "spp:{$this->spp->id}:Juli:2027";

        $pertama = $this->buatGabungan();
        $this->tambahBaris($pertama, $this->siswa, 250000, null, [
            ['tagihan' => $tagihan, 'nominal' => 250000],
        ]);
        $this->tetapkanTotal($pertama, 250000);

        $kedua = $this->buatGabungan();
        $this->tambahBaris($kedua, $this->siswa, 250000, null, [
            ['tagihan' => $tagihan, 'nominal' => 250000],
        ]);
        $this->tetapkanTotal($kedua, 250000);

        $this->assertTrue($this->service->posting($pertama, $this->admin->id)['berhasil']);
        $this->assertFalse($this->service->posting($kedua, $this->admin->id)['berhasil']);

        $this->assertSame(1, Pembayaran::query()
            ->where('siswa_id', $this->siswa->id)
            ->where('bulan_dibayar', 'Juli')
            ->count());
    }

    public function test_nomor_kuitansi_berurutan_setelah_posting_dan_void(): void
    {
        $nomor = [];

        foreach ([1, 2, 3] as $urutan) {
            $gabungan = $this->buatGabungan();
            $this->tambahBaris($gabungan, $this->siswa, 250000);
            $this->tetapkanTotal($gabungan, 250000);

            $this->assertTrue($this->service->posting($gabungan, $this->admin->id)['berhasil']);

            $nomor[] = (int) substr(Pembayaran::query()->latest('id')->value('kode_transaksi'), -4);

            if ($urutan === 2) {
                $this->assertTrue($this->service->voidGabungan($gabungan, $this->admin->id, 'Koreksi nomor')['berhasil']);
            }
        }

        $this->assertSame([1, 2, 3], $nomor);

        $keempat = $this->buatGabungan();
        $this->tambahBaris($keempat, $this->siswa, 250000);
        $this->tetapkanTotal($keempat, 250000);
        $this->assertTrue($this->service->posting($keempat, $this->admin->id)['berhasil']);

        $nomorBaru = (int) substr(Pembayaran::query()->latest('id')->value('kode_transaksi'), -4);
        $this->assertSame(4, $nomorBaru);
    }

    public function test_void_mengembalikan_tagihan_ke_status_semula(): void
    {
        $gabungan = $this->buatGabungan();
        $this->tambahBaris($gabungan, $this->siswa, 250000);
        $this->tetapkanTotal($gabungan, 250000);
        $this->service->posting($gabungan, $this->admin->id);

        $pembayaran = Pembayaran::query()->firstOrFail();

        $this->assertTrue($this->service->voidGabungan($gabungan->fresh(), $this->admin->id, 'Salah input')['berhasil']);

        $this->assertNotNull($pembayaran->fresh()->void_at);
        $this->assertSame(PembayaranGabungan::STATUS_VOID, $gabungan->fresh()->status);

        $terbuka = app(PembayaranService::class)->tagihanTerbuka($this->siswa->fresh());
        $this->assertNotEmpty($terbuka);
        $this->assertSame(3000000, (int) collect($terbuka)->sum('sisa'));
    }

    public function test_impor_transfer_bank_membaca_nis_berawalan_nol_dan_membuat_baris_error(): void
    {
        $gabungan = $this->buatGabungan([
            'sumber' => PembayaranGabungan::SUMBER_TRANSFER_BANK,
            'metode' => PembayaranGabungan::METODE_TRANSFER,
        ]);

        $this->siswa->update(['nis' => '00123456', 'nisn' => '0012345678']);

        $path = storage_path('app/public/import-transfer-test.csv');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, "NIS,JUMLAH,TANGGAL,REFERENSI\n00123456,250000,2027-07-10,TRF-1\n99999999,250000,2027-07-10,TRF-2\n");

        Livewire::test(EditPembayaranGabungan::class, ['record' => $gabungan->getRouteKey()])
            ->call('imporTransfer', $path);

        $gabungan->refresh();

        $this->assertSame(2, $gabungan->baris()->count());
        $this->assertDatabaseHas('pembayaran_gabungan_baris', [
            'gabungan_id' => $gabungan->id,
            'siswa_id' => $this->siswa->id,
        ]);
        $this->assertDatabaseHas('pembayaran_gabungan_baris', [
            'gabungan_id' => $gabungan->id,
            'siswa_id' => null,
            'status_validasi' => PembayaranGabunganBaris::STATUS_ERROR,
        ]);
    }

    public function test_petugas_dapat_membuat_gabungan_tetapi_void_hanya_admin(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        $gabungan = $this->buatGabungan(['dibuat_oleh' => $petugas->id]);
        $this->tambahBaris($gabungan, $this->siswa, 250000);
        $this->tetapkanTotal($gabungan, 250000);

        $this->actingAs($petugas);
        $this->assertTrue($this->service->posting($gabungan, $petugas->id)['berhasil']);

        $this->expectException(\RuntimeException::class);
        $this->service->voidGabungan($gabungan->fresh(), $petugas->id, 'Coba void');
    }

    public function test_halaman_pembayaran_massal_dapat_dibuka(): void
    {
        $gabungan = $this->buatGabungan();

        $this->get(PembayaranGabunganResource::getUrl('index'))->assertOk();
        $this->get(PembayaranGabunganResource::getUrl('create'))->assertOk();
        $this->get(PembayaranGabunganResource::getUrl('edit', ['record' => $gabungan]))->assertOk();
    }

    public function test_setoran_kelas_otomatis_memuat_siswa_kelas(): void
    {
        $this->buatSiswa(['nama' => 'Siti Aminah', 'nisn' => '0098765432', 'nis' => '00987654']);

        $gabungan = $this->buatGabungan([
            'sumber' => PembayaranGabungan::SUMBER_SETORAN_KELAS,
            'kelas_id' => $this->kelas->id,
        ]);

        $this->assertSame(2, $this->service->muatSiswaKelas($gabungan));
        $this->assertSame(2, $gabungan->baris()->count());
        $this->assertSame(0, (int) $gabungan->baris()->sum('jumlah'));
        $this->assertSame(
            2,
            $gabungan->baris()->where('status_validasi', PembayaranGabunganBaris::STATUS_ERROR)->count()
        );

        $this->assertSame(0, $this->service->muatSiswaKelas($gabungan->fresh()));
    }

    public function test_wizard_pembayaran_massal_memuat_siswa_dan_membuat_pembayaran(): void
    {
        $kedua = $this->buatSiswa(['nama' => 'Siti Aminah', 'nisn' => '0098765432', 'nis' => '00987654']);

        Livewire::test(CreatePembayaranGabungan::class)
            ->assertSet('mode', 'kompetensi')
            ->set('jurusan_id', $this->jurusan->id)
            ->set('kelas_id', $this->kelas->id)
            ->assertSet('siswa_ids', [$this->siswa->id, $kedua->id])
            ->set('bulan', ['Juli'])
            ->call('simpan')
            ->assertRedirectContains('/pembayaran/gabungan/');

        $gabungan = PembayaranGabungan::query()->latest('id')->firstOrFail();

        $this->assertSame(PembayaranGabungan::STATUS_DIPOSTING, $gabungan->status);
        $this->assertSame(2, $gabungan->baris()->count());
        $this->assertSame(500000, (int) $gabungan->total_diterima);
        $this->assertSame(2, Pembayaran::query()->where('gabungan_id', $gabungan->id)->count());
        $this->assertSame(250000, (int) Pembayaran::query()->where('siswa_id', $kedua->id)->sum('jumlah_bayar'));

        $this->get(route('pembayaran.gabungan.kuitansi', $gabungan))
            ->assertOk()
            ->assertSee($this->siswa->nama)
            ->assertSee('Juli');
        $this->get(route('pembayaran.gabungan.rekap', $gabungan))
            ->assertOk()
            ->assertSee('REKAP PEMBAYARAN MASSAL');
    }

    public function test_wizard_melewati_bulan_yang_sudah_lunas(): void
    {
        $this->buatSiswa(['nama' => 'Siti Aminah', 'nisn' => '0098765432', 'nis' => '00987654']);

        Pembayaran::query()->create([
            'kode_transaksi' => 'SPP-TEST-LUNAS',
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => now()->format('Y-m-d'),
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2027,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        Livewire::test(CreatePembayaranGabungan::class)
            ->set('jurusan_id', $this->jurusan->id)
            ->set('kelas_id', $this->kelas->id)
            ->set('bulan', ['Juli'])
            ->call('simpan')
            ->assertRedirectContains('/pembayaran/gabungan/');

        $gabungan = PembayaranGabungan::query()->latest('id')->firstOrFail();

        $this->assertSame(1, $gabungan->baris()->count());
        $this->assertSame(250000, (int) $gabungan->total_diterima);
        $this->assertSame(1, Pembayaran::query()->where('gabungan_id', $gabungan->id)->count());
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatGabungan(array $atribut = []): PembayaranGabungan
    {
        return PembayaranGabungan::query()->create(array_merge([
            'kode' => 'PG-TEST-'.Str::upper(Str::random(6)),
            'tanggal' => now()->format('Y-m-d'),
            'sumber' => PembayaranGabungan::SUMBER_CATATAN_MANUAL,
            'metode' => PembayaranGabungan::METODE_TUNAI,
            'total_diterima' => 0,
            'status' => PembayaranGabungan::STATUS_DRAFT,
            'idempotency_token' => (string) Str::uuid(),
            'dibuat_oleh' => $this->admin->id,
        ], $atribut));
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $alokasi
     */
    private function tambahBaris(PembayaranGabungan $gabungan, Siswa $siswa, int $jumlah, ?string $referensi = null, ?array $alokasi = null): PembayaranGabunganBaris
    {
        return $this->service->tambahBaris($gabungan, $siswa->id, $jumlah, $referensi, $alokasi);
    }

    private function tetapkanTotal(PembayaranGabungan $gabungan, int $total): void
    {
        $gabungan->update(['total_diterima' => $total]);
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatSiswa(array $atribut = []): Siswa
    {
        return Siswa::query()->create(array_merge([
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0012345678',
            'nis' => '00123456',
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ], $atribut));
    }
}
