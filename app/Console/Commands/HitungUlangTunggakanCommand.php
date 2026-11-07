<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use Illuminate\Console\Command;

class HitungUlangTunggakanCommand extends Command
{
    protected $signature = 'spp:hitung-ulang-tunggakan {--force : Paksa perbarui tanggal masuk seluruh siswa sesuai tingkat kelas}';

    protected $description = 'Hitung ulang dan sinkronkan tahun & tanggal masuk siswa untuk perhitungan tunggakan';

    public function handle(): int
    {
        $this->info('Memulai sinkronisasi data siswa...');

        $force = (bool) $this->option('force');
        $hasil = Siswa::sinkronkanDataMasuk($force);

        $this->info("Selesai. Total siswa: {$hasil['total']}, diperbarui: {$hasil['diperbarui']}.");

        return self::SUCCESS;
    }
}
