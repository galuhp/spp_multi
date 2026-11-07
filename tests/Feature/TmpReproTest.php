<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\KenaikanKelas;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TmpReproTest extends TestCase
{
    use RefreshDatabase;

    public function test_render_after_selecting_kelas_asal(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->actingAs($admin);

        $jurusan = Jurusan::query()->create(['kode' => 'TKJ', 'nama' => 'Teknik Komputer dan Jaringan']);

        $asal = TahunAjaran::query()->updateOrCreate(['nama' => '2026/2027'], [
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
        ]);
        $tujuan = TahunAjaran::query()->updateOrCreate(['nama' => '2027/2028'], [
            'tanggal_mulai' => '2027-07-01',
            'tanggal_selesai' => '2028-06-30',
            'aktif' => false,
        ]);

        $spp = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'SPP',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 250000,
        ]);

        $kelasAsal = Kelas::query()->create([
            'tahun_ajaran_id' => $asal->id,
            'jurusan_id' => $jurusan->id,
            'nama_kelas' => 'X TKJ 1',
            'tingkat' => '10',
        ]);
        $kelasTujuan = Kelas::query()->create([
            'tahun_ajaran_id' => $tujuan->id,
            'jurusan_id' => $jurusan->id,
            'nama_kelas' => 'XI TKJ 1',
            'tingkat' => '11',
        ]);

        $siswa = Siswa::query()->create([
            'kelas_id' => $kelasAsal->id,
            'spp_id' => $spp->id,
            'tahun_masuk' => 2026,
            'nisn' => '1234567890',
            'nis' => '12345678',
            'nama' => 'Siswa Uji',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
            'tanggal_masuk' => '2026-07-01',
        ]);

        RiwayatKelas::query()->updateOrCreate(
            ['siswa_id' => $siswa->id, 'tahun_ajaran_id' => $asal->id],
            ['kelas_id' => $kelasAsal->id, 'spp_id' => $spp->id, 'status' => RiwayatKelas::STATUS_AKTIF],
        );

        $component = Livewire::test(KenaikanKelas::class)
            ->set('tahun_ajaran_asal_id', $asal->id)
            ->set('tahun_ajaran_tujuan_id', $tujuan->id)
            ->set('kelas_asal_id', $kelasAsal->id)
            ->set('kelas_tujuan_batch_id', $kelasTujuan->id);

        // Force a full render of the page view.
        $component->assertSee('Siswa Uji');
    }
}
