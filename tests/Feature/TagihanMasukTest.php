<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Pembayaran\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagihanMasukTest extends TestCase
{
    use RefreshDatabase;

    public function test_siswa_masuk_2026_tidak_memiliki_tunggakan_tahun_sebelumnya(): void
    {
        $ta = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
        ]);

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
}
