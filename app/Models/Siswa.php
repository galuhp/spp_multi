<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

#[Fillable([
    'user_id',
    'kelas_id',
    'spp_id',
    'nisn',
    'nik',
    'nis',
    'nama',
    'tempat_lahir',
    'tanggal_lahir',
    'agama',
    'jenis_kelamin',
    'alamat',
    'tanggal_masuk',
    'tahun_masuk',
    'status',
    'no_telp',
    'is_archived',
    'archived_at',
    'tanggal_lulus',
])]
class Siswa extends Model
{
    use HasFactory;

    /**
     * Auto-fill tahun_masuk, sync initial RiwayatKelas, and prevent deleting a student with payment history.
     */
    protected static function booted(): void
    {
        static::saving(function (Siswa $siswa): void {
            if (filled($siswa->no_telp)) {
                $siswa->no_telp = \App\Support\ImporSiswa\PembacaNilai::normalisasiHp((string) $siswa->no_telp) ?? trim((string) $siswa->no_telp);
            }

            if ($siswa->kelas_id && (blank($siswa->spp_id) || $siswa->isDirty('kelas_id'))) {
                $kelas = $siswa->relationLoaded('kelas') ? $siswa->kelas : Kelas::with('tahunAjaran')->find($siswa->kelas_id);
                if ($kelas) {
                    $tarif = Spp::untukKelas($kelas);
                    if ($tarif) {
                        $siswa->spp_id = $tarif->id;
                    }
                }
            }
        });

        static::creating(function (Siswa $siswa): void {
            if (blank($siswa->tahun_masuk)) {
                if ($siswa->tanggal_masuk) {
                    $siswa->tahun_masuk = (int) $siswa->tanggal_masuk->format('Y');
                } elseif ($siswa->spp_id && ($spp = Spp::find($siswa->spp_id))) {
                    $siswa->tahun_masuk = Pembayaran::tahunMulaiTarif($spp);
                } else {
                    $siswa->tahun_masuk = (int) now()->year;
                }
            }
        });

        static::created(function (Siswa $siswa): void {
            if (! $siswa->kelas_id) {
                return;
            }

            $kelas = Kelas::find($siswa->kelas_id);
            $tahunAjaranId = $kelas?->tahun_ajaran_id
                ?: TahunAjaran::query()->where('aktif', true)->value('id')
                ?: TahunAjaran::query()->orderByDesc('nama')->value('id');

            if ($tahunAjaranId) {
                RiwayatKelas::query()->firstOrCreate(
                    [
                        'siswa_id' => $siswa->id,
                        'tahun_ajaran_id' => $tahunAjaranId,
                    ],
                    [
                        'kelas_id' => $siswa->kelas_id,
                        'spp_id' => $siswa->spp_id,
                        'status' => $siswa->status === 'lulus'
                            ? RiwayatKelas::STATUS_LULUS
                            : RiwayatKelas::STATUS_AKTIF,
                    ],
                );
            }
        });

        static::deleting(function (Siswa $siswa): void {
            if ($siswa->pembayarans()->exists()) {
                throw new RuntimeException('Siswa dengan riwayat pembayaran tidak dapat dihapus. Arsipkan data siswa.');
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'tahun_masuk' => 'integer',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'tanggal_lulus' => 'date',
        ];
    }

    /**
     * Only students that are active and not archived.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_archived', false)->where('status', 'aktif');
    }

    /**
     * Exclude archived students.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeTanpaArsip(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Only archived students.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeArsip(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    /**
     * Determine whether this student is archived.
     */
    public function isArchived(): bool
    {
        return (bool) $this->is_archived;
    }

    /**
     * User account associated with this student (optional login).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Class that this student belongs to.
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * SPP tariff assigned to this student.
     */
    public function spp(): BelongsTo
    {
        return $this->belongsTo(Spp::class);
    }

    /**
     * Payment records for this student.
     */
    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    /**
     * Government-covered fee markers for this student.
     */
    public function tanggunganPemerintah(): HasMany
    {
        return $this->hasMany(SiswaTanggunganPemerintah::class);
    }

    /**
     * Guardian data of this student.
     */
    public function wali(): HasOne
    {
        return $this->hasOne(Wali::class);
    }

    /**
     * Class history of this student per academic year.
     */
    public function riwayatKelas(): HasMany
    {
        return $this->hasMany(RiwayatKelas::class);
    }

    /**
     * Total amount paid by this student.
     */
    public function totalDibayar(): int
    {
        return (int) $this->pembayarans()->whereNull('void_at')->sum('jumlah_bayar');
    }

    /**
     * Check if a specific month and year has been fully paid.
     */
    public function sudahBayar(string $bulan, int $tahun): bool
    {
        $nominal = (int) ($this->spp?->nominal ?? 0);

        if ($nominal <= 0) {
            return false;
        }

        return (int) $this->pembayarans()
            ->whereNull('void_at')
            ->where('spp_id', $this->spp_id)
            ->where('bulan_dibayar', $bulan)
            ->where('tahun_dibayar', $tahun)
            ->sum('jumlah_bayar') >= $nominal;
    }

    /**
     * Resolves the student's admission year (e.g. 2026).
     */
    public function tahunMasuk(): int
    {
        if (filled($this->tahun_masuk) && (int) $this->tahun_masuk > 2000) {
            return (int) $this->tahun_masuk;
        }

        if ($this->tanggal_masuk) {
            return (int) \Carbon\Carbon::parse($this->tanggal_masuk)->format('Y');
        }

        $taNama = $this->riwayatKelas()
            ->with('tahunAjaran')
            ->get()
            ->sortBy(fn ($r) => $r->tahunAjaran?->tanggal_mulai ?? $r->tahunAjaran?->nama)
            ->first()
            ?->tahunAjaran?->nama;

        if ($taNama && preg_match('/(\d{4})/', $taNama, $m)) {
            return (int) $m[1];
        }

        if ($this->kelas?->tahunAjaran?->nama && preg_match('/(\d{4})/', $this->kelas->tahunAjaran->nama, $m)) {
            return (int) $m[1];
        }

        if ($this->spp_id && ($spp = $this->spp ?? Spp::find($this->spp_id))) {
            return Pembayaran::tahunMulaiTarif($spp);
        }

        return (int) now()->year;
    }

    /**
     * Determine if a given billing period (month + calendar year) is during or after the student's admission.
     */
    public function sudahMasukPadaPeriode(string $bulan, int $tahun): bool
    {
        $tahunMasuk = $this->tahunMasuk();

        if ($this->tanggal_masuk) {
            $bulanMap = [
                'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
                'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
                'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
            ];

            $monthNum = $bulanMap[$bulan] ?? 7;
            $endOfBulan = \Carbon\Carbon::createFromDate($tahun, $monthNum, 1)->endOfMonth();
            $tglMasuk = \Carbon\Carbon::parse($this->tanggal_masuk)->startOfMonth();

            return ! $endOfBulan->lt($tglMasuk);
        }

        // Jika hanya ada tahun masuk (misal 2026):
        // Periode tahun ajaran 2026/2027 mencakup Juli 2026 s.d. Juni 2027.
        // Periode kalender sebelum Juli 2026 (yaitu tahun < 2026, atau tahun == 2026 tapi bulan Jan-Jun) adalah tahun ajaran sebelumnya.
        if ($tahun < $tahunMasuk) {
            return false;
        }

        if ($tahun === $tahunMasuk && in_array($bulan, ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Hitung ulang dan sinkronkan tanggal_masuk dan tahun_masuk untuk seluruh siswa yang sudah ada di database.
     *
     * @return array{total: int, diperbarui: int}
     */
    public static function sinkronkanDataMasuk(bool $force = false): array
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('siswas', 'tanggal_masuk') ||
                ! \Illuminate\Support\Facades\Schema::hasColumn('siswas', 'tahun_masuk')) {
                return ['total' => 0, 'diperbarui' => 0];
            }

            $siswas = static::with(['kelas.tahunAjaran', 'riwayatKelas.tahunAjaran', 'spp'])->get();
            $diperbarui = 0;

            $tahunAktifNama = null;
            if (\Illuminate\Support\Facades\Schema::hasTable('tahun_ajarans')) {
                $tahunAktifNama = \Illuminate\Support\Facades\DB::table('tahun_ajarans')
                    ->where('aktif', true)
                    ->value('nama')
                    ?? \Illuminate\Support\Facades\DB::table('tahun_ajarans')->orderByDesc('nama')->value('nama');
            }

            $tahunAktifInt = 2026;
            if ($tahunAktifNama && preg_match('/^(\d{4})/', $tahunAktifNama, $m)) {
                $tahunAktifInt = (int) $m[1];
            }

            foreach ($siswas as $siswa) {
                $taYear = $tahunAktifInt;
                if ($siswa->kelas?->tahunAjaran?->nama && preg_match('/^(\d{4})/', $siswa->kelas->tahunAjaran->nama, $m)) {
                    $taYear = (int) $m[1];
                }

                $tingkatRaw = $siswa->kelas?->tingkat;
                $tingkat = (int) (Spp::normalisasiTingkat($tingkatRaw) ?? 10);
                $selisih = 0;
                if ($tingkat >= 10 && $tingkat <= 13) {
                    $selisih = $tingkat - 10;
                } elseif ($tingkat >= 7 && $tingkat <= 9) {
                    $selisih = $tingkat - 7;
                } elseif ($tingkat >= 1 && $tingkat <= 6) {
                    $selisih = $tingkat - 1;
                }

                $tahunMasukKalkulasi = max(2000, $taYear - $selisih);

                // Cek riwayat kelas paling awal jika ada
                if ($siswa->riwayatKelas->isNotEmpty()) {
                    $riwayatAwal = $siswa->riwayatKelas
                        ->sortBy(fn ($r) => $r->tahunAjaran?->tanggal_mulai ?? $r->tahunAjaran?->nama)
                        ->first();

                    if ($riwayatAwal?->tahunAjaran?->nama && preg_match('/^(\d{4})/', $riwayatAwal->tahunAjaran->nama, $m)) {
                        $tahunAwalRiwayat = (int) $m[1];
                        if ($tahunAwalRiwayat < $tahunMasukKalkulasi) {
                            $tahunMasukKalkulasi = $tahunAwalRiwayat;
                        }
                    }
                }

                $needsUpdate = false;
                $tglMasuk = $siswa->tanggal_masuk ? \Carbon\Carbon::parse($siswa->tanggal_masuk)->format('Y-m-d') : null;
                $thnMasuk = $siswa->tahun_masuk ? (int) $siswa->tahun_masuk : null;

                if ($force || empty($thnMasuk) || $thnMasuk > $taYear || $thnMasuk < 2000) {
                    if ($siswa->tahun_masuk !== $tahunMasukKalkulasi) {
                        $siswa->tahun_masuk = $tahunMasukKalkulasi;
                        $needsUpdate = true;
                    }
                }

                $targetTgl = "{$siswa->tahun_masuk}-07-01";
                if ($force || empty($tglMasuk)) {
                    if ($tglMasuk !== $targetTgl) {
                        $siswa->tanggal_masuk = $targetTgl;
                        $needsUpdate = true;
                    }
                }

                if ($needsUpdate) {
                    $siswa->saveQuietly();
                    $diperbarui++;
                }
            }

            return [
                'total' => $siswas->count(),
                'diperbarui' => $diperbarui,
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal sinkronkanDataMasuk: ' . $e->getMessage());

            return [
                'total' => 0,
                'diperbarui' => 0,
            ];
        }
    }
}
