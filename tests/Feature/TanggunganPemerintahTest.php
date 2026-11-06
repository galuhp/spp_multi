<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\LaporanTanggunganPemerintah;
use App\Filament\Pages\TagihanSiswa;
use App\Filament\Resources\Siswas\Pages\EditSiswa;
use App\Filament\Resources\Siswas\RelationManagers\TanggunganPemerintahRelationManager;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\SiswaTanggunganPemerintah;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Pembayaran\PembayaranService;
use App\Support\Pembayaran\TanggunganPemerintahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TanggunganPemerintahTest extends TestCase
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
            'nisn' => '0071234561',
            'nis' => '2026001',
            'nama' => 'Ahmad Rifai',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ]);
    }

    public function test_tanggungan_spp_dan_pos_menghilangkan_tagihan_dan_pencabutan_mengembalikan_tagihan(): void
    {
        $sppMarker = SiswaTanggunganPemerintah::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaTanggunganPemerintah::CAKUPAN_SPP,
            'mulai' => '2026-07-01',
        ]);

        $items = collect(app(PembayaranService::class)->tagihanTerbuka($this->siswa));

        $this->assertFalse($items->contains(
            fn (array $item): bool => $item['tipe'] === 'spp' && $item['spp_id'] === $this->spp->id,
        ));
        $this->assertTrue($items->contains(
            fn (array $item): bool => $item['spp_id'] === $this->uangGedung->id,
        ));

        $posMarker = SiswaTanggunganPemerintah::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaTanggunganPemerintah::CAKUPAN_POS,
            'spp_id' => $this->uangGedung->id,
            'mulai' => '2026-07-01',
        ]);

        $this->assertCount(0, app(PembayaranService::class)->tagihanTerbuka($this->siswa));

        $sppMarker->update(['dicabut_at' => now(), 'alasan' => 'Dicabut']);
        $posMarker->update(['dicabut_at' => now(), 'alasan' => 'Dicabut']);

        $items = collect(app(PembayaranService::class)->tagihanTerbuka($this->siswa));

        $this->assertTrue($items->contains(
            fn (array $item): bool => $item['tipe'] === 'spp' && $item['bulan'] === 'November',
        ));
        $this->assertFalse($items->contains(
            fn (array $item): bool => $item['tipe'] === 'spp' && $item['bulan'] === 'Juli',
        ));
        $this->assertTrue($items->contains(
            fn (array $item): bool => $item['spp_id'] === $this->uangGedung->id,
        ));
    }

    public function test_pencabutan_berlaku_pada_bulan_pencabutan(): void
    {
        $marker = SiswaTanggunganPemerintah::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaTanggunganPemerintah::CAKUPAN_SPP,
            'mulai' => '2026-09-15',
        ]);

        $this->assertTrue(
            app(TanggunganPemerintahService::class)->isSppDitanggung($this->siswa, $this->spp, 'September', 2026),
        );

        $marker->update(['dicabut_at' => now(), 'alasan' => 'Dicabut']);

        $this->assertTrue(
            app(TanggunganPemerintahService::class)->isSppDitanggung($this->siswa, $this->spp, 'Oktober', 2026),
        );
        $this->assertFalse(
            app(TanggunganPemerintahService::class)->isSppDitanggung($this->siswa, $this->spp, 'November', 2026),
        );
    }

    public function test_halaman_tagihan_menampilkan_badge_ditanggung_pemerintah(): void
    {
        RolePermission::query()->firstOrCreate([
            'role' => UserRole::Admin,
            'permission' => RolePermission::PORTAL_SISWA,
        ]);

        SiswaTanggunganPemerintah::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaTanggunganPemerintah::CAKUPAN_SPP,
            'mulai' => '2026-07-01',
        ]);

        Livewire::test(TagihanSiswa::class)
            ->call('pilihSiswa', $this->siswa->id)
            ->assertSee('Ditanggung Pemerintah')
            ->assertSee('Rp 250.000');
    }

    public function test_relation_manager_tanggungan_pemerintah_dapat_dibuka(): void
    {
        RolePermission::query()->firstOrCreate([
            'role' => UserRole::Admin,
            'permission' => RolePermission::KESISWAAN,
        ]);

        Livewire::test(TanggunganPemerintahRelationManager::class, [
            'ownerRecord' => $this->siswa,
            'pageClass' => EditSiswa::class,
        ])
            ->assertSuccessful()
            ->assertSee('Tandai Ditanggung Pemerintah')
            ->assertSee('Belum ada tanggungan pemerintah');
    }

    public function test_laporan_memo_menampilkan_data_tanggungan(): void
    {
        RolePermission::query()->firstOrCreate([
            'role' => UserRole::Admin,
            'permission' => RolePermission::LAPORAN,
        ]);

        SiswaTanggunganPemerintah::query()->create([
            'siswa_id' => $this->siswa->id,
            'cakupan' => SiswaTanggunganPemerintah::CAKUPAN_SPP,
            'mulai' => '2026-07-01',
        ]);

        Livewire::test(LaporanTanggunganPemerintah::class)
            ->assertSee('Rekap Ditanggung Pemerintah')
            ->assertSee($this->siswa->nama)
            ->assertSee('Rp 250.000');
    }
}
