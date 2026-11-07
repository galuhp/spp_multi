<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('siswas', 'tahun_masuk')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->unsignedSmallInteger('tahun_masuk')->nullable()->after('tanggal_masuk');
            });

            $siswas = DB::table('siswas')
                ->leftJoin('spps', 'siswas.spp_id', '=', 'spps.id')
                ->select(['siswas.id', 'siswas.tanggal_masuk', 'spps.tahun_ajaran', 'spps.tahun'])
                ->get();

            foreach ($siswas as $siswa) {
                $tahunMasuk = null;

                if (! empty($siswa->tanggal_masuk) && preg_match('/^(\d{4})/', (string) $siswa->tanggal_masuk, $m)) {
                    $tahunMasuk = (int) $m[1];
                } elseif (! empty($siswa->tahun_ajaran) && preg_match('/^(\d{4})/', (string) $siswa->tahun_ajaran, $m)) {
                    $tahunMasuk = (int) $m[1];
                } elseif (! empty($siswa->tahun)) {
                    $tahunMasuk = (int) $siswa->tahun;
                }

                if ($tahunMasuk) {
                    DB::table('siswas')->where('id', $siswa->id)->update(['tahun_masuk' => $tahunMasuk]);
                }
            }
        }

        if (! Schema::hasColumn('riwayat_kelas', 'spp_id')) {
            Schema::table('riwayat_kelas', function (Blueprint $table) {
                $table->foreignId('spp_id')
                    ->nullable()
                    ->after('tahun_ajaran_id')
                    ->constrained('spps')
                    ->nullOnDelete();
            });
        }

        // Backfill riwayat_kelas for existing students who don't have one yet
        $tahunAktifId = DB::table('tahun_ajarans')->where('aktif', true)->value('id')
            ?? DB::table('tahun_ajarans')->orderByDesc('nama')->value('id');

        $siswasTanpaRiwayat = DB::table('siswas')
            ->leftJoin('kelas', 'siswas.kelas_id', '=', 'kelas.id')
            ->select([
                'siswas.id as siswa_id',
                'siswas.kelas_id',
                'siswas.spp_id',
                'siswas.status',
                'kelas.tahun_ajaran_id as kelas_tahun_ajaran_id',
            ])
            ->get();

        $sekarang = now();

        foreach ($siswasTanpaRiwayat as $baris) {
            $taId = $baris->kelas_tahun_ajaran_id ?: $tahunAktifId;

            if (! $taId || ! $baris->kelas_id) {
                continue;
            }

            $ada = DB::table('riwayat_kelas')
                ->where('siswa_id', $baris->siswa_id)
                ->where('tahun_ajaran_id', $taId)
                ->exists();

            if (! $ada) {
                DB::table('riwayat_kelas')->insert([
                    'siswa_id' => $baris->siswa_id,
                    'kelas_id' => $baris->kelas_id,
                    'tahun_ajaran_id' => $taId,
                    'spp_id' => $baris->spp_id,
                    'status' => $baris->status === 'lulus' ? 'lulus' : 'aktif',
                    'created_at' => $sekarang,
                    'updated_at' => $sekarang,
                ]);
            } else {
                DB::table('riwayat_kelas')
                    ->where('siswa_id', $baris->siswa_id)
                    ->where('tahun_ajaran_id', $taId)
                    ->whereNull('spp_id')
                    ->update(['spp_id' => $baris->spp_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('riwayat_kelas', 'spp_id')) {
            Schema::table('riwayat_kelas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('spp_id');
            });
        }

        if (Schema::hasColumn('siswas', 'tahun_masuk')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropColumn('tahun_masuk');
            });
        }
    }
};
