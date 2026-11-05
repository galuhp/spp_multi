<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        Schema::table('spps', function (Blueprint $table) {
            $table->string('tingkat', 5)->nullable()->after('tahun_ajaran');
            $table->unique(['kategori', 'tahun_ajaran', 'tingkat'], 'spps_kategori_tahun_tingkat_unique');
        });

        $this->buatTarifPerTingkat();
        $this->sinkronkanTarifSiswa();
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('spps', function (Blueprint $table) {
            $table->dropUnique('spps_kategori_tahun_tingkat_unique');
            $table->dropColumn('tingkat');
        });
    }

    /**
     * Duplicate each existing monthly SPP tariff for levels X, XI, and XII.
     */
    protected function buatTarifPerTingkat(): void
    {
        $tarifs = DB::table('spps')
            ->where('kategori', 'SPP')
            ->whereNull('tingkat')
            ->get();

        foreach ($tarifs as $tarif) {
            foreach (['10', '11', '12'] as $tingkat) {
                $sudahAda = DB::table('spps')
                    ->where('kategori', 'SPP')
                    ->where('tahun_ajaran', $tarif->tahun_ajaran)
                    ->where('tingkat', $tingkat)
                    ->exists();

                if ($sudahAda) {
                    continue;
                }

                DB::table('spps')->insert([
                    'kategori' => 'SPP',
                    'tipe' => $tarif->tipe ?? 'bulanan',
                    'nama' => $tarif->nama,
                    'tahun' => $tarif->tahun,
                    'tahun_ajaran' => $tarif->tahun_ajaran,
                    'tingkat' => $tingkat,
                    'nominal' => $tarif->nominal,
                    'keterangan' => $tarif->keterangan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Point each student at the tariff that matches their class level.
     */
    protected function sinkronkanTarifSiswa(): void
    {
        $siswas = DB::table('siswas')
            ->leftJoin('kelas', 'kelas.id', '=', 'siswas.kelas_id')
            ->leftJoin('tahun_ajarans', 'tahun_ajarans.id', '=', 'kelas.tahun_ajaran_id')
            ->join('spps as tarif_lama', 'tarif_lama.id', '=', 'siswas.spp_id')
            ->select([
                'siswas.id',
                'siswas.spp_id',
                'kelas.tingkat as tingkat_kelas',
                'tahun_ajarans.nama as tahun_ajaran_kelas',
                'tarif_lama.tahun_ajaran as tahun_ajaran_tarif',
            ])
            ->get();

        foreach ($siswas as $siswa) {
            $tingkat = $this->normalisasiTingkat($siswa->tingkat_kelas);

            if ($tingkat === null) {
                continue;
            }

            $tahunAjaran = $siswa->tahun_ajaran_kelas ?: $siswa->tahun_ajaran_tarif;

            $tarif = DB::table('spps')
                ->where('kategori', 'SPP')
                ->where('tahun_ajaran', $tahunAjaran)
                ->where('tingkat', $tingkat)
                ->first();

            if ($tarif && (int) $tarif->id !== (int) $siswa->spp_id) {
                DB::table('siswas')->where('id', $siswa->id)->update(['spp_id' => $tarif->id]);
            }
        }
    }

    protected function normalisasiTingkat(?string $tingkat): ?string
    {
        $tingkat = mb_strtoupper(trim((string) $tingkat));

        return match ($tingkat) {
            '10', 'X' => '10',
            '11', 'XI' => '11',
            '12', 'XII' => '12',
            default => is_numeric($tingkat) ? (string) $tingkat : null,
        };
    }
};
