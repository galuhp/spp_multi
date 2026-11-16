<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['seri', 'tahun', 'nomor_terakhir'])]
class NomorKuitansiUrutan extends Model
{
    use BelongsToTenant;
    protected $table = 'nomor_kuitansi_urutan';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'nomor_terakhir' => 'integer',
        ];
    }
}
