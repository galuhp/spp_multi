<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TagihanSiswa;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RiwayatKelas;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Pembayaran\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TagihanMasukTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_masuk_2026_tidak_memiliki_tunggakan_tahun_sebelumnya(): void
    {
        $ta = $this->tahunAjaran('2026/2027', true);

        $jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        // SPP 2025/2026 (Tingkat 10 tahun lalu)
        Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2025,
            'tahun_ajaran' => '2025/2026',
            'tingkat' => '10',
            'nominal' => 100000,
        ]);

        // SPP 2026/2027 (Tahun sekarang)
        $spp2026 = Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 100000,
        ]);

        // Siswa baru masuk pada Juli 2026
        $siswa = Siswa::create([
            'kelas_id' => $kelas->id,
            'spp_id' => $spp2026->id,
            'nisn' => '1234567890',
            'nis' => '26001',
            'nama' => 'Budi Baru Masuk',
            'jenis_kelamin' => 'L',
            'tanggal_masuk' => '2026-07-01',
            'tahun_masuk' => 2026,
            'status' => 'aktif',
        ]);

        $this->assertEquals(2026, $siswa->tahunMasuk());
        $this->assertFalse($siswa->sudahMasukPadaPeriode('Juli', 2025));
        $this->assertFalse($siswa->sudahMasukPadaPeriode('Januari', 2026));
        $this->assertTrue($siswa->sudahMasukPadaPeriode('Juli', 2026));
        $this->assertTrue($siswa->sudahMasukPadaPeriode('Agustus', 2026));

        $service = app(PembayaranService::class);
        $tagihan = $service->tagihanTerbuka($siswa);

        // Pastikan tidak ada satupun tagihan dari tahun 2025
        $tagihan2025 = array_filter($tagihan, fn ($item) => ($item['tahun'] ?? null) === 2025);
        $this->assertEmpty($tagihan2025, 'Siswa yang masuk 2026 tidak boleh memiliki tagihan dari tahun 2025.');
    }

    public function test_sinkronisasi_tidak_menurunkan_tahun_masuk_siswa_baru_di_tingkat_atas(): void
    {
        $ta = $this->tahunAjaran('2026/2027', true);

        $jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'XI RPL 1',
            'tingkat' => '11',
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        $spp = Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '11',
            'nominal' => 100000,
        ]);

        $siswa = $this->buatSiswa($kelas, $spp, 'Siswa Baru XI', '26002', '26002');

        // Simulasikan data yang sempat dihitung ulang sebagai angkatan 2025.
        Siswa::query()->whereKey($siswa->id)->update([
            'tahun_masuk' => 2025,
            'tanggal_masuk' => '2025-07-01',
            'created_at' => '2026-07-01 00:00:00',
        ]);

        Siswa::sinkronkanDataMasuk(true);

        $siswa->refresh();

        $this->assertSame(2026, (int) $siswa->tahun_masuk);
        $this->assertSame('2026-07-01', $siswa->tanggal_masuk?->format('Y-m-d'));
    }

    public function test_sinkronisasi_tidak_menaikkan_tahun_masuk_siswa_lama_yang_tinggal_kelas(): void
    {
        $this->tahunAjaran('2024/2025');

        $ta = $this->tahunAjaran('2026/2027', true);

        $jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'XI RPL 1',
            'tingkat' => '11',
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        $spp = Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '11',
            'nominal' => 100000,
        ]);

        $siswa = Siswa::create([
            'kelas_id' => $kelas->id,
            'spp_id' => $spp->id,
            'nisn' => '26005',
            'nis' => '26005',
            'nama' => 'Siswa Mengulang',
            'jenis_kelamin' => 'L',
            'tanggal_masuk' => '2024-07-01',
            'tahun_masuk' => 2024,
            'status' => 'aktif',
        ]);

        RiwayatKelas::query()->create([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => TahunAjaran::query()->where('nama', '2024/2025')->value('id'),
            'kelas_id' => $kelas->id,
            'status' => RiwayatKelas::STATUS_AKTIF,
        ]);

        Siswa::query()->whereKey($siswa->id)->update([
            'created_at' => '2024-07-01 00:00:00',
        ]);

        Siswa::sinkronkanDataMasuk(true);

        $siswa->refresh();

        $this->assertSame(2024, (int) $siswa->tahun_masuk);
        $this->assertSame('2024-07-01', $siswa->tanggal_masuk?->format('Y-m-d'));
    }

    public function test_siswa_baru_2026_di_tingkat_atas_tidak_memiliki_tunggakan_prior(): void
    {
        $ta = $this->tahunAjaran('2026/2027', true);

        $this->tahunAjaran('2025/2026');

        $jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'XI RPL 1',
            'tingkat' => '11',
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        $sppSekarang = Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '11',
            'nominal' => 100000,
        ]);

        Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2025,
            'tahun_ajaran' => '2025/2026',
            'tingkat' => '10',
            'nominal' => 100000,
        ]);

        Spp::create([
            'kategori' => 'Uang Gedung',
            'nama' => 'Uang Gedung',
            'tipe' => 'sekali_bayar',
            'tahun' => 2024,
            'tahun_ajaran' => '2024/2025',
            'tingkat' => '10',
            'nominal' => 1200000,
        ]);

        $siswa = $this->buatSiswa($kelas, $sppSekarang, 'Siswa Baru XI', '26003', '26003');

        Siswa::query()->whereKey($siswa->id)->update([
            'tahun_masuk' => 2025,
            'tanggal_masuk' => '2025-07-01',
            'created_at' => '2026-07-01 00:00:00',
        ]);

        Siswa::sinkronkanDataMasuk(true);

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        RolePermission::query()->firstOrCreate([
            'role' => UserRole::Admin,
            'permission' => RolePermission::PORTAL_SISWA,
        ]);
        $this->actingAs($admin);

        $component = Livewire::test(TagihanSiswa::class)
            ->call('pilihSiswa', $siswa->id);

        $tagihan = $component->instance()->getTagihanDataProperty();

        $this->assertSame([], $tagihan['tunggakanPrior']);
        $this->assertSame(0, $tagihan['totalTunggakanPrior']);
    }

    public function test_pos_non_spp_sebelum_tahun_masuk_tidak_dihitung(): void
    {
        $ta = $this->tahunAjaran('2026/2027', true);

        $jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        $spp = Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 100000,
        ]);

        $nonSppLama = Spp::create([
            'kategori' => 'Uang Gedung',
            'nama' => 'Uang Gedung',
            'tipe' => 'sekali_bayar',
            'tahun' => 2024,
            'tahun_ajaran' => '2024/2025',
            'tingkat' => '10',
            'nominal' => 1200000,
        ]);

        $siswa = $this->buatSiswa($kelas, $spp, 'Siswa Baru X', '26004', '26004');

        $tagihan = app(PembayaranService::class)->tagihanTerbuka($siswa);

        $this->assertFalse(
            collect($tagihan)->contains(fn (array $item): bool => $item['spp_id'] === $nonSppLama->id),
            'Pos non-SPP sebelum tahun masuk tidak boleh menjadi tagihan.'
        );
    }

    public function test_tarif_non_spp_bulanan_ditagih_per_bulan(): void
    {
        $ta = $this->tahunAjaran('2026/2027', true);

        $jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        $spp = Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 100000,
        ]);

        $tabungan = Spp::create([
            'kategori' => 'Tabungan',
            'nama' => 'Tabungan per bulan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 35000,
        ]);

        $siswa = $this->buatSiswa($kelas, $spp, 'Siswa Tabungan', '26006', '26006');

        $items = collect(app(PembayaranService::class)->tagihanTerbuka($siswa));
        $tabunganItems = $items->where('spp_id', $tabungan->id);

        $this->assertCount(12, $tabunganItems);
        $this->assertSame(12 * 35000, (int) $tabunganItems->sum('sisa'));
        $this->assertTrue($tabunganItems->every(
            fn (array $item): bool => $item['tipe'] === 'non_spp' && filled($item['bulan']),
        ));

        Pembayaran::create([
            'kode_transaksi' => 'TRX-TABUNGAN-JULI',
            'user_id' => User::factory()->create(['role' => UserRole::Petugas])->id,
            'siswa_id' => $siswa->id,
            'spp_id' => $tabungan->id,
            'tgl_bayar' => '2026-07-15',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 35000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $items = collect(app(PembayaranService::class)->tagihanTerbuka($siswa));
        $tabunganItems = $items->where('spp_id', $tabungan->id);

        $this->assertCount(11, $tabunganItems);
        $this->assertFalse($tabunganItems->contains(
            fn (array $item): bool => $item['bulan'] === 'Juli',
        ));
    }

    public function test_halaman_tagihan_menampilkan_tarif_non_spp_bulanan_per_bulan(): void
    {
        $ta = $this->tahunAjaran('2026/2027', true);

        $jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
            'jurusan_id' => $jurusan->id,
            'tahun_ajaran_id' => $ta->id,
        ]);

        $spp = Spp::create([
            'kategori' => 'SPP',
            'nama' => 'SPP Bulanan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 100000,
        ]);

        $tabungan = Spp::create([
            'kategori' => 'Tabungan',
            'nama' => 'Tabungan per bulan',
            'tipe' => 'bulanan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 35000,
        ]);

        $siswa = $this->buatSiswa($kelas, $spp, 'Siswa Tabungan', '26007', '26007');

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        RolePermission::query()->firstOrCreate([
            'role' => UserRole::Admin,
            'permission' => RolePermission::PORTAL_SISWA,
        ]);
        $this->actingAs($admin);

        $component = Livewire::test(TagihanSiswa::class)
            ->call('pilihSiswa', $siswa->id);

        $tagihan = $component->instance()->getTagihanDataProperty();
        $barisTabungan = collect($tagihan['nonSppStatus'])
            ->filter(fn (array $item): bool => $item['tarif']->id === $tabungan->id);

        $this->assertCount(12, $barisTabungan);
        $this->assertSame(12 * 35000, (int) $tagihan['totalNonSppBelumLunas']);

        // Matriks bulanan menggabungkan SPP dan tarif lain bertipe bulanan.
        $this->assertCount(12, $tagihan['matriksBulanan']);

        $component
            ->assertSee('Matriks Pembayaran Bulanan')
            ->assertSee('Tabungan per bulan');

        $juli = collect($tagihan['matriksBulanan'])->firstWhere('bulan', 'Juli');

        $this->assertNotNull($juli);
        $this->assertNotNull($juli['spp']);
        $this->assertCount(1, $juli['pos_lain']);
        $this->assertSame('Tabungan per bulan', $juli['pos_lain'][0]['nama']);
        $this->assertSame(35000, $juli['pos_lain'][0]['nominal']);
        $this->assertSame(100000 + 35000, $juli['total']);
        $this->assertSame([], $tagihan['nonSppSekaliBayar']);

        // Tarif sekali bayar tidak masuk matriks bulanan, melainkan grup tersendiri.
        $gedung = Spp::create([
            'kategori' => 'Uang Gedung',
            'nama' => 'Uang Gedung',
            'tipe' => 'sekali_bayar',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 2000000,
        ]);

        $tagihan = $component->instance()->getTagihanDataProperty();

        $this->assertCount(12, $tagihan['matriksBulanan']);
        $this->assertCount(1, $tagihan['nonSppSekaliBayar']);
        $this->assertSame($gedung->id, $tagihan['nonSppSekaliBayar'][0]['tarif']->id);
    }

    private function buatSiswa(Kelas $kelas, Spp $spp, string $nama, string $nisn, string $nis): Siswa
    {
        return Siswa::create([
            'kelas_id' => $kelas->id,
            'spp_id' => $spp->id,
            'nisn' => $nisn,
            'nis' => $nis,
            'nama' => $nama,
            'jenis_kelamin' => 'L',
            'tanggal_masuk' => '2026-07-01',
            'tahun_masuk' => 2026,
            'status' => 'aktif',
        ]);
    }

    private function tahunAjaran(string $nama, bool $aktif = false): TahunAjaran
    {
        $tahunMulai = (int) substr($nama, 0, 4);

        $tahunAjaran = TahunAjaran::query()->firstOrCreate(
            ['nama' => $nama],
            [
                'tanggal_mulai' => "{$tahunMulai}-07-01",
                'tanggal_selesai' => ($tahunMulai + 1).'-06-30',
                'aktif' => $aktif,
            ],
        );

        if ($aktif && ! $tahunAjaran->aktif) {
            $tahunAjaran->update(['aktif' => true]);
        }

        return $tahunAjaran;
    }
}
