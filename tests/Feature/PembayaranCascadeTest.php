<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Pembayarans\Pages\CreatePembayaran;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PembayaranCascadeTest extends TestCase
{
    use RefreshDatabase;

    private Jurusan $rpl;

    private Jurusan $tkj;

    private Kelas $kelasRpl;

    private Kelas $kelasTkj;

    private Spp $spp;

    private Siswa $siswaRpl;

    private Siswa $siswaTkj;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2027/2028',
            'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2028-06-30',
            'aktif' => true,
        ]);

        $this->rpl = Jurusan::query()->create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak']);
        $this->tkj = Jurusan::query()->create(['kode' => 'TKJ', 'nama' => 'Teknik Komputer Jaringan']);

        $this->kelasRpl = Kelas::query()->create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'jurusan_id' => $this->rpl->id,
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
        ]);
        $this->kelasTkj = Kelas::query()->create([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'jurusan_id' => $this->tkj->id,
            'nama_kelas' => 'X TKJ 1',
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

        $this->siswaRpl = Siswa::query()->create([
            'kelas_id' => $this->kelasRpl->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0012345678',
            'nis' => '12345678',
            'nama' => 'Budi RPL',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        $this->siswaTkj = Siswa::query()->create([
            'kelas_id' => $this->kelasTkj->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0098765432',
            'nis' => '87654321',
            'nama' => 'Siti TKJ',
            'jenis_kelamin' => 'P',
            'status' => 'aktif',
        ]);
    }

    public function test_jurusan_kelas_dan_siswa_berurutan_pada_form_pembayaran(): void
    {
        Livewire::test(CreatePembayaran::class)
            ->assertFormFieldDisabled('siswa_id')
            ->fillForm(['jurusan_id' => $this->rpl->id])
            ->assertFormFieldEnabled('kelas_id')
            ->fillForm(['kelas_id' => $this->kelasRpl->id])
            ->assertFormFieldEnabled('siswa_id')
            ->fillForm([
                'siswa_id' => $this->siswaRpl->id,
                'jenis_transaksi' => 'spp',
                'bulan_dibayar' => ['Juli'],
                'tgl_bayar' => '2027-07-10',
                'metode_pembayaran' => 'Tunai',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswaRpl->id,
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2027,
            'jumlah_bayar' => 250000,
        ]);
    }

    public function test_pencarian_nama_dapat_memilih_siswa_tanpa_jurusan_dan_kelas(): void
    {
        Livewire::test(CreatePembayaran::class)
            ->fillForm(['cara_pilih' => 'cari'])
            ->assertFormFieldHidden('jurusan_id')
            ->assertFormFieldHidden('kelas_id')
            ->assertFormFieldEnabled('siswa_id')
            ->fillForm([
                'siswa_id' => $this->siswaTkj->id,
                'jenis_transaksi' => 'spp',
                'bulan_dibayar' => ['Agustus'],
                'tgl_bayar' => '2027-08-10',
                'metode_pembayaran' => 'Transfer Bank',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswaTkj->id,
            'bulan_dibayar' => 'Agustus',
            'tahun_dibayar' => 2027,
        ]);
    }
}
