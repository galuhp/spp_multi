<?php

namespace Tests\Feature;

use App\Enums\StatusBarisImpor;
use App\Enums\UserRole;
use App\Filament\Pages\ImporSiswa;
use App\Models\ImportSiswaStaging;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\Wali;
use App\Support\ImporSiswa\ImporSiswaService;
use App\Support\ImporSiswa\ReferensiImporSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImporSiswaMassalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TahunAjaran $tahunAjaran;

    private Jurusan $jurusan;

    private Kelas $kelas;

    private Spp $spp;

    /**
     * @var list<string>
     */
    private array $urutanKolom = [
        'nisn',
        'nik',
        'nis',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'tanggal_masuk',
        'kode_kompetensi',
        'kelas',
        'nama_ayah',
        'nama_ibu',
        'nama_wali',
        'hp_wali',
    ];

    /**
     * @var list<string>
     */
    private array $berkasSementara = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->tahunAjaran = TahunAjaran::query()->updateOrCreate(
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

        $this->kelas = Kelas::query()->create([
            'jurusan_id' => $this->jurusan->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
        ]);

        $this->spp = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'nominal' => 250000,
        ]);

        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        foreach ($this->berkasSementara as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        parent::tearDown();
    }

    public function test_nisn_sembilan_dan_sebelas_digit_ditolak(): void
    {
        [$batch] = $this->impor([
            $this->barisValid(['nisn' => '123456789', 'nis' => '2026001', 'nik' => '3273010101010001']),
            $this->barisValid(['nisn' => '12345678901', 'nis' => '2026002', 'nik' => '3273010101010002']),
        ]);

        $baris = $this->barisBatch($batch);

        $this->assertSame(StatusBarisImpor::Error, $baris[0]->status);
        $this->assertSame(StatusBarisImpor::Error, $baris[1]->status);
        $this->assertStringContainsString('NISN harus tepat 10 digit angka', $baris[0]->alasan);
        $this->assertStringContainsString('NISN harus tepat 10 digit angka', $baris[1]->alasan);
    }

    public function test_nisn_mengandung_huruf_ditolak(): void
    {
        [$batch] = $this->impor([
            $this->barisValid(['nisn' => '12345ABCDE']),
        ]);

        $baris = $this->barisBatch($batch);

        $this->assertSame(StatusBarisImpor::Error, $baris[0]->status);
        $this->assertStringContainsString('NISN harus tepat 10 digit angka', $baris[0]->alasan);
    }

    public function test_nisn_duplikat_di_berkas_ditandai(): void
    {
        [$batch] = $this->impor([
            $this->barisValid(['nisn' => '0071234561', 'nis' => '2026001', 'nik' => '3273010101010001']),
            $this->barisValid(['nisn' => '0071234561', 'nis' => '2026002', 'nik' => '3273010101010002']),
        ]);

        $baris = $this->barisBatch($batch);

        $this->assertNotSame(StatusBarisImpor::Error, $baris[0]->status);
        $this->assertSame(StatusBarisImpor::Error, $baris[1]->status);
        $this->assertStringContainsString('NISN duplikat di berkas', $baris[1]->alasan);
    }

    public function test_nisn_duplikat_di_database_ditolak(): void
    {
        $this->siswaLama(['nisn' => '0099999999', 'nis' => '2025001', 'nama' => 'Siswa Lama']);

        [$batch] = $this->impor([
            $this->barisValid(['nisn' => '0099999999', 'nis' => '2026001', 'nik' => '3273010101010001']),
        ]);

        $baris = $this->barisBatch($batch);

        $this->assertSame(StatusBarisImpor::Error, $baris[0]->status);
        $this->assertStringContainsString('NISN sudah terdaftar', $baris[0]->alasan);
    }

    public function test_nisn_berawalan_nol_disimpan_sebagai_teks(): void
    {
        [$batch, $service] = $this->impor([
            $this->barisValid(['nisn' => '0123456789']),
        ]);

        $ringkasan = $service->simpanBatch($batch, false);

        $this->assertSame(1, $ringkasan['berhasil']);
        $this->assertDatabaseHas('siswas', [
            'nisn' => '0123456789',
            'status' => 'aktif',
        ]);
    }

    public function test_impor_memakai_tarif_sesuai_tingkat_kelas(): void
    {
        $tarifX = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'tahun' => 2027,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 250000,
        ]);

        [$batch, $service] = $this->impor([
            $this->barisValid([
                'nisn' => '0071234599',
                'nis' => '2026099',
                'nik' => '3273010101010099',
            ]),
        ]);

        $ringkasan = $service->simpanBatch($batch, false);

        $this->assertSame(1, $ringkasan['berhasil']);
        $this->assertDatabaseHas('siswas', [
            'nisn' => '0071234599',
            'spp_id' => $tarifX->id,
        ]);
    }

    public function test_nik_enam_belas_digit_numerik_dan_ilmiah_tetap_utuh(): void
    {
        [$batch, $service] = $this->impor(
            [
                $this->barisValid([
                    'nisn' => '0071234561',
                    'nik' => '9876543210987654',
                    'nis' => '2026001',
                ]),
                $this->barisValid([
                    'nisn' => '0071234562',
                    'nik' => '1.234567890123456E+15',
                    'nis' => '2026002',
                ]),
            ],
            false,
            [[0, 1]],
        );

        $ringkasan = $service->simpanBatch($batch, false);

        $this->assertSame(2, $ringkasan['berhasil']);
        $this->assertDatabaseHas('siswas', ['nik' => '9876543210987654']);
        $this->assertDatabaseHas('siswas', ['nik' => '1234567890123456']);
    }

    public function test_kelas_yang_tidak_ada_ditolak(): void
    {
        [$batch] = $this->impor([
            $this->barisValid(['kelas' => 'X RPL 9']),
        ]);

        $baris = $this->barisBatch($batch);

        $this->assertSame(StatusBarisImpor::Error, $baris[0]->status);
        $this->assertStringContainsString('tidak terdaftar pada tahun ajaran tujuan', $baris[0]->alasan);
    }

    public function test_impor_ulang_berkas_yang_sama_tidak_menggandakan_data(): void
    {
        $baris = [$this->barisValid()];

        [$batchPertama, $service] = $this->impor($baris);
        $service->simpanBatch($batchPertama, false);

        [$batchKedua] = $this->impor($baris);
        $ringkasanKedua = $service->simpanBatch($batchKedua, false);

        $this->assertSame(1, Siswa::query()->count());
        $this->assertSame(1, Wali::query()->count());
        $this->assertSame(1, RiwayatKelas::query()->count());
        $this->assertSame(0, $ringkasanKedua['berhasil']);
        $this->assertSame(1, $ringkasanKedua['dilewati']);
    }

    public function test_opsi_perbarui_menimpa_data_siswa_tanpa_menggandakan(): void
    {
        [$batchPertama, $service] = $this->impor([
            $this->barisValid(['nama_lengkap' => 'Nama Awal']),
        ]);
        $service->simpanBatch($batchPertama, false);

        [$batchKedua] = $this->impor([
            $this->barisValid(['nama_lengkap' => 'Nama Diperbarui']),
        ], true);

        $ringkasan = $service->simpanBatch($batchKedua, true);

        $this->assertSame(1, $ringkasan['berhasil']);
        $this->assertSame(1, Siswa::query()->count());
        $this->assertSame(1, Wali::query()->count());
        $this->assertSame(1, RiwayatKelas::query()->count());
        $this->assertDatabaseHas('siswas', ['nama' => 'Nama Diperbarui']);
    }

    public function test_template_impor_dapat_diunduh(): void
    {
        $respons = $this->get(route('impor-siswa.template'));

        $respons->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $respons->headers->get('content-type'),
        );
    }

    public function test_laporan_error_dapat_diunduh(): void
    {
        [$batch] = $this->impor([
            $this->barisValid(['nisn' => '123456789']),
        ]);

        $respons = $this->get(route('impor-siswa.error', $batch));

        $respons->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $respons->headers->get('content-type'),
        );
    }

    public function test_admin_dapat_membuka_halaman_impor(): void
    {
        $this->get(route('filament.admin.pages.impor-siswa'))
            ->assertOk()
            ->assertSee('Unggah Berkas Impor')
            ->assertSee('impor-siswa');
    }

    public function test_halaman_impor_menampilkan_pratinjau_lalu_menyimpan(): void
    {
        [$batch] = $this->impor([$this->barisValid()]);

        Livewire::test(ImporSiswa::class)
            ->set('batchId', $batch)
            ->assertSee('Pratinjau Validasi')
            ->call('gantiFilter', 'valid')
            ->assertSet('filter', 'valid')
            ->call('simpan')
            ->assertSet('batchId', null)
            ->assertSee('Hasil Impor');

        $this->assertDatabaseHas('siswas', ['nama' => 'Andi Wijaya', 'status' => 'aktif']);
    }

    public function test_petugas_dapat_membuka_halaman_impor_dan_siswa_tidak(): void
    {
        $petugas = User::factory()->create(['role' => UserRole::Petugas]);

        $this->actingAs($petugas)
            ->get(route('filament.admin.pages.impor-siswa'))
            ->assertOk();

        $siswa = User::factory()->create(['role' => UserRole::Siswa]);

        $this->actingAs($siswa)
            ->get(route('filament.admin.pages.impor-siswa'))
            ->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function barisValid(array $ganti = []): array
    {
        return array_merge([
            'nisn' => '0071234561',
            'nik' => '3273010101010001',
            'nis' => '2026001',
            'nama_lengkap' => 'Andi Wijaya',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2010-01-01',
            'agama' => 'Islam',
            'alamat' => 'Jl. Merdeka No. 1',
            'tanggal_masuk' => '2026-07-15',
            'kode_kompetensi' => 'RPL',
            'kelas' => 'X RPL 1',
            'nama_ayah' => 'Budi Wijaya',
            'nama_ibu' => 'Siti Aminah',
            'nama_wali' => '',
            'hp_wali' => '081234567890',
        ], $ganti);
    }

    /**
     * @param  array<int, array<string, string>>  $baris
     * @param  list<array{0: int, 1: int}>  $selNumerik
     * @return array{0: string, 1: ImporSiswaService}
     */
    private function impor(array $baris, bool $perbarui = false, array $selNumerik = []): array
    {
        $berkas = $this->buatBerkas($baris, $selNumerik);
        $service = app(ImporSiswaService::class);
        $batch = $service->mulaiBatch(
            $berkas,
            $this->tahunAjaran->id,
            $this->spp->id,
            $this->admin->id,
            'uji-impor.xlsx',
        );

        $service->validasiBatch($batch, $perbarui);

        return [$batch, $service];
    }

    /**
     * @param  array<int, array<string, string>>  $baris
     * @param  list<array{0: int, 1: int}>  $selNumerik
     */
    private function buatBerkas(array $baris, array $selNumerik = []): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(ReferensiImporSiswa::KOLOM, null, 'A1');

        foreach ($baris as $indeksBaris => $nilaiBaris) {
            foreach ($this->urutanKolom as $indeksKolom => $kunci) {
                $sel = $sheet->getCell([$indeksKolom + 1, $indeksBaris + 2]);
                $nilai = (string) ($nilaiBaris[$kunci] ?? '');

                if (in_array([$indeksBaris, $indeksKolom], $selNumerik, true)) {
                    $sel->setValueExplicit((int) $nilai, DataType::TYPE_NUMERIC);
                } else {
                    $sel->setValueExplicit($nilai, DataType::TYPE_STRING);
                }
            }
        }

        $berkas = sys_get_temp_dir().DIRECTORY_SEPARATOR.'impor-siswa-'.uniqid().'.xlsx';
        (new Xlsx($spreadsheet))->save($berkas);
        $spreadsheet->disconnectWorksheets();

        $this->berkasSementara[] = $berkas;

        return $berkas;
    }

    /**
     * @return list<ImportSiswaStaging>
     */
    private function barisBatch(string $batch): array
    {
        return ImportSiswaStaging::query()
            ->where('batch_id', $batch)
            ->orderBy('baris')
            ->get()
            ->all();
    }

    /**
     * @param  array<string, string>  $ganti
     */
    private function siswaLama(array $ganti): Siswa
    {
        return Siswa::query()->create(array_merge([
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0099999999',
            'nis' => '2025001',
            'nama' => 'Siswa Lama',
            'jenis_kelamin' => 'L',
            'status' => 'aktif',
        ], $ganti));
    }
}
