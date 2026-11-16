<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'gabungan_id',
    'siswa_id',
    'jumlah',
    'referensi',
    'alokasi',
    'status_validasi',
    'pesan',
    'pembayaran_id',
    'void_oleh',
    'void_alasan',
    'void_at',
])]
class PembayaranGabunganBaris extends Model
{
    use BelongsToTenant;
    use HasFactory;

    public const STATUS_OK = 'ok';

    public const STATUS_ERROR = 'error';

    public const STATUS_PERINGATAN = 'peringatan';

    protected $table = 'pembayaran_gabungan_baris';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'alokasi' => 'array',
            'void_at' => 'datetime',
        ];
    }

    public function gabungan(): BelongsTo
    {
        return $this->belongsTo(PembayaranGabungan::class, 'gabungan_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class);
    }

    public function pemberiVoid(): BelongsTo
    {
        return $this->belongsTo(User::class, 'void_oleh');
    }

    public function isVoid(): bool
    {
        return $this->void_at !== null;
    }
}
