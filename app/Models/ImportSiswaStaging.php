<?php

namespace App\Models;

use App\Enums\StatusBarisImpor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'batch_id',
    'user_id',
    'nama_file',
    'tahun_ajaran_id',
    'spp_id',
    'baris',
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
    'potongan',
    'status',
    'alasan',
    'siswa_id',
    'ditimpa',
    'tersimpan',
])]
class ImportSiswaStaging extends Model
{
    protected $table = 'import_siswa_staging';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'potongan' => 'integer',
            'status' => StatusBarisImpor::class,
            'ditimpa' => 'boolean',
            'tersimpan' => 'boolean',
        ];
    }

    /**
     * Staff user who uploaded the file.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Target academic year of the import batch.
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * SPP tariff assigned to imported students.
     */
    public function spp(): BelongsTo
    {
        return $this->belongsTo(Spp::class);
    }

    /**
     * Student created or updated by this row, if any.
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }
}
