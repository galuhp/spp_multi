<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ImporDataMaster;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\ImporMaster\ImporMasterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImporMasterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $petugas;

    private TahunAjaran $tahunAjaran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->petugas = User::factory()->create([
            'role' => UserRole::Petugas,
        ]);

        $this->tahunAjaran = TahunAjaran::create([
            'nama' => '2026/2027',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2027-06-30',
            'aktif' => true,
        ]);
    }

    private function createXlsx(array $headers, array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $colIdx => $h) {
            $sheet->setCellValueByColumnAndRow($colIdx + 1, 1, $h);
        }

        foreach ($rows as $rowIdx => $row) {
            foreach ($row as $colIdx => $val) {
                $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowIdx + 2, $val);
            }
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_import_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    public function test_hanya_admin_yang_dapat_mengunduh_template(): void
    {
        // Petugas cannot download
        $this->actingAs($this->petugas);
        $this->get(route('impor.template.jurusan'))->assertForbidden();
        $this->get(route('impor.template.kelas'))->assertForbidden();
        $this->get(route('impor.template.tarif'))->assertForbidden();

        // Admin can download
        $this->actingAs($this->admin);
        $this->get(route('impor.template.jurusan'))->assertOk();
        $this->get(route('impor.template.kelas'))->assertOk();
        $this->get(route('impor.template.tarif'))->assertOk();
    }

    public function test_hanya_admin_yang_dapat_mengakses_halaman_impor_data_master(): void
    {
        $this->actingAs($this->petugas);
        $this->assertFalse(ImporDataMaster::canAccess());

        $this->actingAs($this->admin);
        $this->assertTrue(ImporDataMaster::canAccess());
    }

    public function test_impor_jurusan_berhasil(): void
    {
        $this->actingAs($this->admin);

        $path = $this->createXlsx(
            ['kode', 'nama', 'deskripsi'],
            [
                ['RPL', 'Rekayasa Perangkat Lunak', 'Pemrograman'],
                ['TKJ', 'Teknik Komputer dan Jaringan', 'Jaringan'],
            ]
        );

        $service = app(ImporMasterService::class);
        $hasil = $service->imporJurusan($path);

        $this->assertEquals(2, $hasil['berhasil']);
        $this->assertEquals(2, $hasil['baru']);
        $this->assertDatabaseHas('jurusans', ['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak']);
        $this->assertDatabaseHas('jurusans', ['kode' => 'TKJ', 'nama' => 'Teknik Komputer dan Jaringan']);

        // Test update existing
        $pathUpdate = $this->createXlsx(
            ['kode', 'nama', 'deskripsi'],
            [
                ['RPL', 'Rekayasa Perangkat Lunak & Gim', 'Pemrograman Update'],
            ]
        );

        $hasilUpdate = $service->imporJurusan($pathUpdate);
        $this->assertEquals(1, $hasilUpdate['berhasil']);
        $this->assertEquals(1, $hasilUpdate['diperbarui']);
        $this->assertDatabaseHas('jurusans', ['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak & Gim']);

        @unlink($path);
        @unlink($pathUpdate);
    }

    public function test_impor_kelas_berhasil(): void
    {
        $this->actingAs($this->admin);

        $jurusan = Jurusan::create(['kode' => 'RPL', 'nama' => 'Rekayasa Perangkat Lunak']);
        $guru = User::factory()->create(['name' => 'Siti Aminah, S.Pd.', 'username' => 'siti.aminah', 'role' => UserRole::WaliKelas]);

        $path = $this->createXlsx(
            ['nama_kelas', 'tingkat', 'kode_jurusan', 'tahun_ajaran', 'wali_kelas'],
            [
                ['X RPL 1', '10', 'RPL', '2026/2027', 'siti.aminah'],
                ['XI RPL 1', '11', 'RPL', '2026/2027', ''],
            ]
        );

        $service = app(ImporMasterService::class);
        $hasil = $service->imporKelas($path);

        $this->assertEquals(2, $hasil['berhasil']);
        $this->assertEquals(2, $hasil['baru']);

        $kelas1 = Kelas::where('nama_kelas', 'X RPL 1')->first();
        $this->assertNotNull($kelas1);
        $this->assertEquals('10', $kelas1->tingkat);
        $this->assertEquals($jurusan->id, $kelas1->jurusan_id);
        $this->assertEquals($guru->id, $kelas1->wali_kelas_id);

        @unlink($path);
    }

    public function test_impor_tarif_berhasil_termasuk_tarif_semua_tingkat(): void
    {
        $this->actingAs($this->admin);

        $path = $this->createXlsx(
            ['kategori', 'nama', 'tipe', 'tahun_ajaran', 'tingkat', 'nominal', 'keterangan'],
            [
                ['SPP', 'SPP Bulanan', 'bulanan', '2026/2027', '', '100000', 'Berlaku semua tingkat'],
                ['Biaya Ujian', 'PTS Ganjil', 'sekali_bayar', '2026/2027', '10', '250000', 'PTS Ganjil X'],
            ]
        );

        $service = app(ImporMasterService::class);
        $hasil = $service->imporTarif($path);

        $this->assertEquals(2, $hasil['berhasil']);
        $this->assertEquals(2, $hasil['baru']);

        $sppGlobal = Spp::where('kategori', 'SPP')->where('nama', 'SPP Bulanan')->first();
        $this->assertNotNull($sppGlobal);
        $this->assertNull($sppGlobal->tingkat);
        $this->assertEquals(100000, $sppGlobal->nominal);
        $this->assertEquals('bulanan', $sppGlobal->tipe);

        $sppUjian = Spp::where('nama', 'PTS Ganjil')->first();
        $this->assertNotNull($sppUjian);
        $this->assertEquals('10', $sppUjian->tingkat);
        $this->assertEquals(250000, $sppUjian->nominal);
        $this->assertEquals('sekali_bayar', $sppUjian->tipe);

        @unlink($path);
    }
}
