<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The production database may already contain these columns after a
     * previously interrupted deploy, so each change is applied defensively.
     */
    public function up(): void
    {
        $this->tambahKolomSpps();
        $this->perbaruiPembayaran();
    }

    /**
     * Add only the SPP columns that are still missing.
     */
    private function tambahKolomSpps(): void
    {
        if (! Schema::hasTable('spps')) {
            return;
        }

        if (! Schema::hasColumn('spps', 'kategori')) {
            Schema::table('spps', function (Blueprint $table): void {
                $table->string('kategori', 50)->default('SPP')->after('id');
            });
        }

        if (! Schema::hasColumn('spps', 'tipe')) {
            Schema::table('spps', function (Blueprint $table): void {
                $table->string('tipe', 30)->default('bulanan')->after('kategori');
            });
        }

        if (! Schema::hasColumn('spps', 'nama')) {
            Schema::table('spps', function (Blueprint $table): void {
                $table->string('nama', 100)->nullable()->after('tipe');
            });
        }
    }

    /**
     * Remove the legacy unique index and allow nullable non-SPP months.
     */
    private function perbaruiPembayaran(): void
    {
        if (! Schema::hasTable('pembayarans')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('pembayarans'));

        $hasSiswaIndex = $indexes->contains(
            fn (array $index): bool => in_array('siswa_id', $index['columns'], true)
                && $index['name'] !== 'unique_siswa_spp_bulan_tahun',
        );

        if (! $hasSiswaIndex) {
            Schema::table('pembayarans', function (Blueprint $table): void {
                $table->index('siswa_id', 'pembayarans_siswa_id_index');
            });
        }

        $indexNames = $indexes->pluck('name')->all();

        if (in_array('unique_siswa_spp_bulan_tahun', $indexNames, true)) {
            Schema::table('pembayarans', function (Blueprint $table): void {
                $table->dropUnique('unique_siswa_spp_bulan_tahun');
            });
        }

        if (Schema::hasColumn('pembayarans', 'bulan_dibayar')) {
            Schema::table('pembayarans', function (Blueprint $table): void {
                $table->string('bulan_dibayar', 20)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pembayarans') && Schema::hasColumn('pembayarans', 'bulan_dibayar')) {
            Schema::table('pembayarans', function (Blueprint $table): void {
                $table->string('bulan_dibayar', 20)->nullable(false)->change();
                $table->unique(['siswa_id', 'bulan_dibayar', 'tahun_dibayar'], 'unique_siswa_spp_bulan_tahun');
            });
        }

        if (Schema::hasTable('spps')) {
            $columns = array_values(array_filter(
                ['kategori', 'tipe', 'nama'],
                fn (string $column): bool => Schema::hasColumn('spps', $column),
            ));

            if ($columns !== []) {
                Schema::table('spps', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
