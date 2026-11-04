<?php

namespace App\Console\Commands;

use App\Models\ImportSiswaStaging;
use Illuminate\Console\Command;

class BersihkanStagingImporSiswa extends Command
{
    protected $signature = 'impor-siswa:bersihkan-staging {--hari=7 : Umur maksimal staging dalam hari}';

    protected $description = 'Hapus data staging impor siswa yang lebih tua dari batas hari';

    public function handle(): int
    {
        $hari = max(1, (int) $this->option('hari'));

        $jumlah = ImportSiswaStaging::query()
            ->where('created_at', '<', now()->subDays($hari))
            ->delete();

        $this->info("{$jumlah} baris staging impor siswa dihapus.");

        return self::SUCCESS;
    }
}
