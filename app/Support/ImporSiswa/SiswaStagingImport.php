<?php

namespace App\Support\ImporSiswa;

use App\Models\ImportSiswaStaging;
use Maatwebsite\Excel\Concerns\RemembersChunkOffset;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use RuntimeException;

class SiswaStagingImport implements ToArray, WithChunkReading, WithStartRow
{
    use RemembersChunkOffset;

    public const BATAS_BARIS = 2000;

    private int $jumlahBaris = 0;

    public function __construct(
        private string $batchId,
        private int $tahunAjaranId,
        private int $sppId,
        private ?int $userId,
        private string $namaFile,
    ) {}

    /**
     * @param  array<int, array<int, mixed>>  $array
     */
    public function array(array $array): void
    {
        $offset = $this->getChunkOffset() ?? 2;
        $sekarang = now();
        $data = [];

        foreach ($array as $index => $baris) {
            if (PembacaNilai::barisKosong($baris)) {
                continue;
            }

            $this->jumlahBaris++;

            if ($this->jumlahBaris > self::BATAS_BARIS) {
                throw new RuntimeException('Berkas melebihi batas 2.000 baris data.');
            }

            $data[] = [
                'batch_id' => $this->batchId,
                'user_id' => $this->userId,
                'nama_file' => $this->namaFile,
                'tahun_ajaran_id' => $this->tahunAjaranId,
                'spp_id' => $this->sppId,
                'baris' => $offset + $index,
                'nisn' => PembacaNilai::teks($baris[0] ?? null),
                'nik' => PembacaNilai::teks($baris[1] ?? null),
                'nis' => PembacaNilai::teks($baris[2] ?? null),
                'nama_lengkap' => PembacaNilai::teks($baris[3] ?? null),
                'jenis_kelamin' => PembacaNilai::teks($baris[4] ?? null),
                'tempat_lahir' => PembacaNilai::teks($baris[5] ?? null),
                'tanggal_lahir' => PembacaNilai::tanggal($baris[6] ?? null),
                'agama' => PembacaNilai::teks($baris[7] ?? null),
                'alamat' => PembacaNilai::teks($baris[8] ?? null),
                'tanggal_masuk' => PembacaNilai::tanggal($baris[9] ?? null),
                'kode_kompetensi' => PembacaNilai::teks($baris[10] ?? null),
                'kelas' => PembacaNilai::teks($baris[11] ?? null),
                'nama_ayah' => PembacaNilai::teks($baris[12] ?? null),
                'nama_ibu' => PembacaNilai::teks($baris[13] ?? null),
                'nama_wali' => PembacaNilai::teks($baris[14] ?? null),
                'hp_wali' => PembacaNilai::teks($baris[15] ?? null),
                'status' => 'valid',
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ];
        }

        if ($data !== []) {
            ImportSiswaStaging::query()->insert($data);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function jumlahBaris(): int
    {
        return $this->jumlahBaris;
    }
}
