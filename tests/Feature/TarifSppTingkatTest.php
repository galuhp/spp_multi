<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Siswas\Pages\CreateSiswa;
use App\Filament\Resources\Spps\Pages\CreateSpp;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\KenaikanKelas\ProsesKelasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class TarifSppTingkatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TahunAjaran $tahunAjaran;

    private Jurusan $jurusan;

    private Kelas $kelasX;

    private Kelas $kelasXI;

    /**
     * @var array<string, Spp>
     */
    private array $tarif = [];

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

        $this->jurusan = Jurusan::query()->create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->kelasX = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
        ]);

        $this->kelasXI = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => 'XI RPL 1',
            'tingkat' => '11',
        ]);

        foreach (Spp::TINGKAT_LIST as $tingkat => $label) {
            $this->tarif[$tingkat] = Spp::query()->create([
                'kategori' => Spp::KATEGORI_SPP,
                'tipe' => Spp::TIPE_BULANAN,
                'tahun' => 2027,
                'tahun_ajaran' => '2026/2027',
                'tingkat' => $tingkat,
                'nominal' => 250000,
            ]);
        }
    }

    public function test_tarif_spp_per_tingkat_memiliki_nominal_sama_dan_resolver_sesuai_kelas(): void
    {
        $this->assertSame('10', (string) $this->tarif['10']->tingkat);
        $this->assertSame(250000, $this->tarif['10']->nominal);
        $this->assertSame(250000, $this->tarif['11']->nominal);
        $this->assertSame(250000, $this->tarif['12']->nominal);

        $this->assertSame($this->tarif['10']->id, Spp::untukKelas($this->kelasX)?->id);
        $this->assertSame($this->tarif['11']->id, Spp::untukKelas($this->kelasXI)?->id);
        $this->assertSame($this->tarif['10']->id, Spp::untukTingkat('X', '2026/2027')?->id);
        $this->assertSame($this->tarif['12']->id, Spp::untukTingkat('XII', '2026/2027')?->id);
    }

    public function test_create_siswa_memakai_tarif_sesuai_tingkat_kelas(): void
    {
        Livewire::test(CreateSiswa::class)
            ->fillForm([
                'nisn' => '7123456777',
                'nis' => '20260770',
                'nama' => 'Siswa Baru',
                'jenis_kelamin' => 'L',
                'kelas_id' => $this->kelasX->id,
                'spp_id' => $this->tarif['10']->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('siswas', [
            'nisn' => '7123456777',
            'spp_id' => $this->tarif['10']->id,
        ]);

        $user = User::query()->where('username', '7123456777')->firstOrFail();
        $this->assertFalse(Hash::check('7123456777', $user->password));
    }

    public function test_tarif_non_spp_bulanan_dengan_nominal_tidak_bulat_dapat_disimpan(): void
    {
        Livewire::test(CreateSpp::class)
            ->fillForm([
                'kategori' => Spp::KATEGORI_UANG_GEDUNG,
                'tipe' => Spp::TIPE_BULANAN,
                'nama' => 'Tabungan per bulan',
                'tahun' => 2026,
                'tahun_ajaran' => '2026/2027',
                'tingkat' => '10',
                'nominal' => 35000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('spps', [
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
            'tipe' => Spp::TIPE_BULANAN,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 35000,
        ]);
    }

    public function test_tarif_spp_wajib_memilih_tingkat(): void
    {
        Livewire::test(CreateSpp::class)
            ->fillForm([
                'kategori' => Spp::KATEGORI_SPP,
                'tipe' => Spp::TIPE_BULANAN,
                'tahun' => 2028,
                'tahun_ajaran' => '2027/2028',
                'nominal' => 250000,
            ])
            ->call('create')
            ->assertHasFormErrors(['tingkat' => 'required']);

        Livewire::test(CreateSpp::class)
            ->fillForm([
                'kategori' => Spp::KATEGORI_SPP,
                'tipe' => Spp::TIPE_BULANAN,
                'tahun' => 2028,
                'tahun_ajaran' => '2027/2028',
                'tingkat' => '10',
                'nominal' => 250000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('spps', [
            'tahun_ajaran' => '2027/2028',
            'tingkat' => '10',
            'nominal' => 250000,
        ]);
    }

    public function test_tarif_spp_tidak_boleh_duplikat_tingkat_dan_tahun_ajaran(): void
    {
        Livewire::test(CreateSpp::class)
            ->fillForm([
                'kategori' => Spp::KATEGORI_SPP,
                'tipe' => Spp::TIPE_BULANAN,
                'tahun' => 2027,
                'tahun_ajaran' => '2026/2027',
                'tingkat' => '10',
                'nominal' => 250000,
            ])
            ->call('create')
            ->assertHasFormErrors(['tingkat' => 'unique']);
    }

    public function test_tarif_non_spp_tetap_dapat_dibuat_tanpa_tingkat(): void
    {
        Livewire::test(CreateSpp::class)
            ->fillForm([
                'kategori' => Spp::KATEGORI_UANG_GEDUNG,
                'tipe' => Spp::TIPE_SEKALI_BAYAR,
                'nama' => 'Uang Gedung Angkatan 2026',
                'tahun' => 2027,
                'tahun_ajaran' => '2026/2027',
                'nominal' => 1000000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('spps', [
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => null,
        ]);
    }

    public function test_kenaikan_kelas_memperbarui_tarif_siswa(): void
    {
        $tahunAjaranAsal = TahunAjaran::query()->create([
            'nama' => '2025/2026',
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2026-06-30',
            'aktif' => false,
        ]);

        $kelasAsal = Kelas::query()->create([
            'tahun_ajaran_id' => $tahunAjaranAsal->id,
            'jurusan_id' => $this->jurusan->id,
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
        ]);

        $tarifAsal = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'tahun' => 2026,
            'tahun_ajaran' => '2025/2026',
            'tingkat' => '10',
            'nominal' => 225000,
        ]);

        $siswa = Siswa::query()->create([
            'kelas_id' => $kelasAsal->id,
            'spp_id' => $tarifAsal->id,
            'nisn' => '0071234561',
            'nis' => '2026001',
            'nama' => 'Ahmad Rifai',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);

        app(ProsesKelasService::class)->naikKelas(
            $tahunAjaranAsal->id,
            $this->tahunAjaran->id,
            $kelasAsal->id,
            [
                $siswa->id => [
                    'status' => RiwayatKelas::STATUS_NAIK,
                    'kelas_tujuan_id' => $this->kelasXI->id,
                ],
            ],
            $this->admin->id,
        );

        $siswa->refresh();

        $this->assertSame($this->kelasXI->id, $siswa->kelas_id);
        $this->assertSame($this->tarif['11']->id, $siswa->spp_id);
    }
}
