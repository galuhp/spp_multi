<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\Kelas\Pages\CreateKelas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KelasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Jurusan $jurusan;

    private TahunAjaran $tahunAjaran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($this->admin);

        $this->jurusan = Jurusan::query()->create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->tahunAjaran = TahunAjaran::query()->create([
            'nama' => '2027/2028',
            'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2028-06-30',
            'aktif' => true,
        ]);
    }

    public function test_kelas_baru_menyimpan_tahun_ajaran(): void
    {
        $this->post(route('kelas.store'), [
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nama_kelas' => 'X RPL 9',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ])->assertRedirect(route('kelas.index'));

        $this->assertDatabaseHas('kelas', [
            'nama_kelas' => 'X RPL 9',
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);
    }

    public function test_nama_kelas_boleh_sama_pada_tahun_ajaran_berbeda(): void
    {
        $tahunAjaranLain = TahunAjaran::query()->create([
            'nama' => '2028/2029',
            'tanggal_mulai' => '2028-07-01',
            'tanggal_selesai' => '2029-06-30',
            'aktif' => false,
        ]);

        Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nama_kelas' => 'X RPL 9',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ]);

        $this->post(route('kelas.store'), [
            'tahun_ajaran_id' => $tahunAjaranLain->id,
            'nama_kelas' => 'X RPL 9',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Kelas::query()->where('nama_kelas', 'X RPL 9')->count());
    }

    public function test_nama_kelas_tidak_boleh_ganda_pada_tahun_ajaran_yang_sama(): void
    {
        Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nama_kelas' => 'X RPL 9',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ]);

        $this->post(route('kelas.store'), [
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nama_kelas' => 'X RPL 9',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ])->assertSessionHasErrors('nama_kelas');

        $this->assertSame(1, Kelas::query()->where('nama_kelas', 'X RPL 9')->count());
    }

    public function test_tahun_ajaran_wajib_dipilih(): void
    {
        $this->post(route('kelas.store'), [
            'nama_kelas' => 'X RPL 9',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ])->assertSessionHasErrors('tahun_ajaran_id');
    }

    public function test_form_kelas_menampilkan_pilihan_tahun_ajaran(): void
    {
        $this->get(route('kelas.create'))
            ->assertOk()
            ->assertSee('Tahun Ajaran')
            ->assertSee('2027/2028');
    }

    public function test_form_ubah_kelas_menampilkan_tahun_ajaran_terpilih(): void
    {
        $kelas = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nama_kelas' => 'X RPL 9',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ]);

        $this->get(route('kelas.edit', $kelas->id))
            ->assertOk()
            ->assertSee('2027/2028')
            ->assertSee('X RPL 9');
    }

    public function test_form_tambah_kelas_filament_dapat_dibuka(): void
    {
        $this->get(route('filament.admin.resources.kelas.create'))
            ->assertOk()
            ->assertSee('Tahun Ajaran');
    }

    public function test_filament_menolak_nama_kelas_ganda_pada_tahun_ajaran_yang_sama(): void
    {
        Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nama_kelas' => 'X RPL 9',
            'tingkat' => '10',
            'jurusan_id' => $this->jurusan->id,
        ]);

        Livewire::test(CreateKelas::class)
            ->fillForm([
                'tahun_ajaran_id' => $this->tahunAjaran->id,
                'nama_kelas' => 'X RPL 9',
                'tingkat' => '10',
                'jurusan_id' => $this->jurusan->id,
            ])
            ->call('create')
            ->assertHasFormErrors(['nama_kelas']);
    }
}
