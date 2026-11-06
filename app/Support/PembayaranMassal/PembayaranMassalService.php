<?php

namespace App\Support\PembayaranMassal;

use App\Models\LogAktivitas;
use App\Models\Pembayaran;
use App\Models\PembayaranGabungan;
use App\Models\PembayaranGabunganBaris;
use App\Models\Siswa;
use App\Support\Pembayaran\PembayaranService;
use App\Support\Pembayaran\PembayaranTidakValidException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PembayaranMassalService
{
    public function __construct(
        protected PembayaranService $pembayaran,
    ) {}

    /**
     * Add a student line to a draft batch.
     *
     * @param  array<int, array<string, mixed>>|null  $alokasiManual
     */
    public function tambahBaris(
        PembayaranGabungan $gabungan,
        int $siswaId,
        int $jumlah,
        ?string $referensi = null,
        ?array $alokasiManual = null,
    ): PembayaranGabunganBaris {
        $this->pastikanDraft($gabungan);

        $siswa = Siswa::query()->findOrFail($siswaId);
        $validasi = $this->pembayaran->validasiBaris($siswa, $jumlah, $alokasiManual);

        $duplikat = $gabungan->baris()
            ->where('siswa_id', $siswa->id)
            ->whereNull('void_at')
            ->exists();

        $status = $validasi['status'];
        $pesan = $validasi['pesan'];

        if ($status === 'ok' && $duplikat) {
            $status = PembayaranGabunganBaris::STATUS_PERINGATAN;
            $pesan = 'Siswa ini muncul lebih dari sekali dalam gabungan. Pastikan total alokasi tidak melebihi sisa tagihan.';
        }

        return $gabungan->baris()->create([
            'siswa_id' => $siswa->id,
            'jumlah' => $jumlah,
            'referensi' => $referensi,
            'alokasi' => $validasi['alokasi'],
            'status_validasi' => $status,
            'pesan' => $pesan,
        ]);
    }

    /**
     * Update a draft line.
     *
     * @param  array<int, array<string, mixed>>|null  $alokasiManual
     */
    public function perbaruiBaris(
        PembayaranGabunganBaris $baris,
        int $jumlah,
        ?string $referensi = null,
        ?array $alokasiManual = null,
    ): void {
        $this->pastikanDraft($baris->gabungan);

        $siswa = $baris->siswa;
        $validasi = $this->pembayaran->validasiBaris($siswa, $jumlah, $alokasiManual);

        $duplikat = $baris->gabungan->baris()
            ->where('siswa_id', $siswa->id)
            ->whereKeyNot($baris->id)
            ->whereNull('void_at')
            ->exists();

        $status = $validasi['status'];
        $pesan = $validasi['pesan'];

        if ($status === 'ok' && $duplikat) {
            $status = PembayaranGabunganBaris::STATUS_PERINGATAN;
            $pesan = 'Siswa ini muncul lebih dari sekali dalam gabungan. Pastikan total alokasi tidak melebihi sisa tagihan.';
        }

        $baris->update([
            'jumlah' => $jumlah,
            'referensi' => $referensi,
            'alokasi' => $validasi['alokasi'],
            'status_validasi' => $status,
            'pesan' => $pesan,
        ]);
    }

    /**
     * Revalidate every draft line.
     */
    public function validasiSemua(PembayaranGabungan $gabungan): void
    {
        $this->pastikanDraft($gabungan);

        foreach ($gabungan->baris()->with(['siswa.spp', 'siswa.kelas'])->get() as $baris) {
            $validasi = $this->pembayaran->validasiBaris($baris->siswa, (int) $baris->jumlah, $baris->alokasi);

            $duplikat = $gabungan->baris()
                ->where('siswa_id', $baris->siswa_id)
                ->whereKeyNot($baris->id)
                ->whereNull('void_at')
                ->exists();

            $status = $validasi['status'];
            $pesan = $validasi['pesan'];

            if ($status === 'ok' && $duplikat) {
                $status = PembayaranGabunganBaris::STATUS_PERINGATAN;
                $pesan = 'Siswa ini muncul lebih dari sekali dalam gabungan. Pastikan total alokasi tidak melebihi sisa tagihan.';
            }

            $baris->update([
                'alokasi' => $validasi['alokasi'],
                'status_validasi' => $status,
                'pesan' => $pesan,
            ]);
        }
    }

    /**
     * Determine whether the batch may be posted.
     *
     * @return array{boleh: bool, alasan: ?string, total_baris: int, selisih: int}
     */
    public function cekPosting(PembayaranGabungan $gabungan): array
    {
        $totalBaris = (int) $gabungan->baris()->whereNull('void_at')->sum('jumlah');
        $selisih = (int) $gabungan->total_diterima - $totalBaris;

        if (! $gabungan->isDraft()) {
            return ['boleh' => false, 'alasan' => 'Gabungan tidak berstatus draft.', 'total_baris' => $totalBaris, 'selisih' => $selisih];
        }

        if ($gabungan->baris()->whereNull('void_at')->count() === 0) {
            return ['boleh' => false, 'alasan' => 'Belum ada baris siswa.', 'total_baris' => $totalBaris, 'selisih' => $selisih];
        }

        if ($gabungan->baris()->where('status_validasi', PembayaranGabunganBaris::STATUS_ERROR)->whereNull('void_at')->exists()) {
            return ['boleh' => false, 'alasan' => 'Masih ada baris error.', 'total_baris' => $totalBaris, 'selisih' => $selisih];
        }

        if ($selisih !== 0) {
            return ['boleh' => false, 'alasan' => 'Total diterima belum sama dengan total baris.', 'total_baris' => $totalBaris, 'selisih' => $selisih];
        }

        if ($totalBaris <= 0) {
            return ['boleh' => false, 'alasan' => 'Total baris harus lebih dari 0.', 'total_baris' => $totalBaris, 'selisih' => $selisih];
        }

        return ['boleh' => true, 'alasan' => null, 'total_baris' => $totalBaris, 'selisih' => $selisih];
    }

    /**
     * Post the batch in one transaction. Idempotent for an already posted batch.
     *
     * @return array{berhasil: bool, pesan: ?string, baris_error_id: ?int}
     */
    public function posting(PembayaranGabungan $gabungan, int $userId): array
    {
        $gabungan->refresh();

        // Posting ulang dengan token yang sama tidak boleh membuat pembayaran ganda.
        if ($gabungan->isPosted()) {
            return ['berhasil' => true, 'pesan' => null, 'baris_error_id' => null];
        }

        $cek = $this->cekPosting($gabungan);

        if (! $cek['boleh']) {
            return ['berhasil' => false, 'pesan' => $cek['alasan'], 'baris_error_id' => null];
        }

        $barisGagal = null;

        try {
            Cache::lock("pembayaran-gabungan:{$gabungan->id}", 30)->block(10, function () use ($gabungan, $userId): void {
                DB::transaction(function () use ($gabungan, $userId): void {
                    /** @var PembayaranGabungan $terkunci */
                    $terkunci = PembayaranGabungan::query()->whereKey($gabungan->id)->lockForUpdate()->firstOrFail();

                    if ($terkunci->isPosted()) {
                        return;
                    }

                    if (! $terkunci->isDraft()) {
                        throw new PembayaranTidakValidException('Gabungan tidak dapat diposting pada status saat ini.');
                    }

                    $terkunci->update(['status' => PembayaranGabungan::STATUS_MEMPROSES]);

                    $totalBaris = (int) $terkunci->baris()->whereNull('void_at')->sum('jumlah');

                    if ($totalBaris !== (int) $terkunci->total_diterima) {
                        throw new PembayaranTidakValidException('Total diterima berubah dan tidak sama dengan total baris.');
                    }

                    $daftarBaris = $terkunci->baris()
                        ->whereNull('void_at')
                        ->orderBy('id')
                        ->get();

                    foreach ($daftarBaris as $baris) {
                        $baris = PembayaranGabunganBaris::query()->whereKey($baris->id)->lockForUpdate()->firstOrFail();
                        $siswa = Siswa::query()->findOrFail($baris->siswa_id);

                        $validasi = $this->pembayaran->validasiBaris($siswa, (int) $baris->jumlah, $baris->alokasi);

                        if ($validasi['status'] === 'error') {
                            throw new PembayaranTidakValidException($validasi['pesan'] ?? 'Baris tidak valid.', $baris->id);
                        }

                        $pembayaran = $this->pembayaran->buatPembayaran(
                            $siswa,
                            (int) $baris->jumlah,
                            $validasi['alokasi'],
                            $terkunci->metode,
                            $terkunci->tanggal->format('Y-m-d'),
                            $terkunci->catatan,
                            $userId,
                            $terkunci,
                            $baris,
                        );

                        $baris->update([
                            'alokasi' => $validasi['alokasi'],
                            'status_validasi' => PembayaranGabunganBaris::STATUS_OK,
                            'pesan' => null,
                            'pembayaran_id' => $pembayaran[0]->id ?? null,
                        ]);
                    }

                    $terkunci->update([
                        'status' => PembayaranGabungan::STATUS_DIPOSTING,
                        'diposting_oleh' => $userId,
                        'diposting_at' => now(),
                    ]);

                    LogAktivitas::catat(
                        'pembayaran_gabungan.diposting',
                        "Pembayaran gabungan {$terkunci->kode} diposting ({$daftarBaris->count()} siswa).",
                        $terkunci,
                        [
                            'gabungan_id' => $terkunci->id,
                            'kode' => $terkunci->kode,
                            'jumlah_siswa' => $daftarBaris->count(),
                            'total' => (int) $terkunci->total_diterima,
                        ]
                    );
                });
            });
        } catch (PembayaranTidakValidException $exception) {
            $barisGagal = $exception->barisId;

            PembayaranGabungan::query()->whereKey($gabungan->id)->update(['status' => PembayaranGabungan::STATUS_DRAFT]);

            if ($barisGagal) {
                PembayaranGabunganBaris::query()->whereKey($barisGagal)->update([
                    'status_validasi' => PembayaranGabunganBaris::STATUS_ERROR,
                    'pesan' => $exception->getMessage(),
                ]);
            }

            return ['berhasil' => false, 'pesan' => $exception->getMessage(), 'baris_error_id' => $barisGagal];
        } catch (\Throwable $exception) {
            PembayaranGabungan::query()->whereKey($gabungan->id)->update(['status' => PembayaranGabungan::STATUS_DRAFT]);

            return ['berhasil' => false, 'pesan' => $exception->getMessage(), 'baris_error_id' => null];
        }

        return ['berhasil' => true, 'pesan' => null, 'baris_error_id' => null];
    }

    /**
     * Void a whole posted batch (admin only).
     */
    public function voidGabungan(PembayaranGabungan $gabungan, int $userId, string $alasan): array
    {
        $this->pastikanAdmin();
        $gabungan->refresh();

        if (! $gabungan->isPosted()) {
            return ['berhasil' => false, 'pesan' => 'Hanya gabungan yang sudah diposting yang dapat di-void.'];
        }

        DB::transaction(function () use ($gabungan, $userId, $alasan): void {
            $terkunci = PembayaranGabungan::query()->whereKey($gabungan->id)->lockForUpdate()->firstOrFail();

            Pembayaran::query()
                ->where('gabungan_id', $terkunci->id)
                ->whereNull('void_at')
                ->update([
                    'void_at' => now(),
                    'void_alasan' => $alasan,
                    'void_oleh' => $userId,
                ]);

            PembayaranGabunganBaris::query()
                ->where('gabungan_id', $terkunci->id)
                ->whereNull('void_at')
                ->update([
                    'void_at' => now(),
                    'void_alasan' => $alasan,
                    'void_oleh' => $userId,
                ]);

            $terkunci->update([
                'status' => PembayaranGabungan::STATUS_VOID,
                'void_at' => now(),
                'void_alasan' => $alasan,
                'void_oleh' => $userId,
            ]);

            LogAktivitas::catat(
                'pembayaran_gabungan.void',
                "Pembayaran gabungan {$terkunci->kode} di-void. Alasan: {$alasan}",
                $terkunci,
                [
                    'gabungan_id' => $terkunci->id,
                    'alasan' => $alasan,
                    'divoid_oleh' => $userId,
                ]
            );
        });

        return ['berhasil' => true, 'pesan' => null];
    }

    /**
     * Void a single posted line (admin only).
     */
    public function voidBaris(PembayaranGabunganBaris $baris, int $userId, string $alasan): array
    {
        $this->pastikanAdmin();

        $gabungan = $baris->gabungan()->first();

        if (! $gabungan?->isPosted()) {
            return ['berhasil' => false, 'pesan' => 'Baris hanya dapat di-void dari gabungan yang sudah diposting.'];
        }

        if ($baris->isVoid()) {
            return ['berhasil' => false, 'pesan' => 'Baris ini sudah di-void.'];
        }

        DB::transaction(function () use ($baris, $userId, $alasan): void {
            Pembayaran::query()
                ->where('gabungan_baris_id', $baris->id)
                ->whereNull('void_at')
                ->update([
                    'void_at' => now(),
                    'void_alasan' => $alasan,
                    'void_oleh' => $userId,
                ]);

            $baris->update([
                'void_at' => now(),
                'void_alasan' => $alasan,
                'void_oleh' => $userId,
            ]);

            LogAktivitas::catat(
                'pembayaran_gabungan.baris_void',
                "Baris pembayaran gabungan {$baris->gabungan->kode} untuk {$baris->siswa?->nama} di-void. Alasan: {$alasan}",
                $baris,
                ['gabungan_id' => $baris->gabungan_id, 'baris_id' => $baris->id, 'alasan' => $alasan]
            );
        });

        return ['berhasil' => true, 'pesan' => null];
    }

    /**
     * Populate a class collection batch with all active students in the class.
     *
     * Students are added with amount 0 and an error message so the officer can
     * remove those not participating and fill the amounts.
     */
    public function muatSiswaKelas(PembayaranGabungan $gabungan): int
    {
        $this->pastikanDraft($gabungan);

        if ($gabungan->sumber !== PembayaranGabungan::SUMBER_SETORAN_KELAS || ! $gabungan->kelas_id) {
            return 0;
        }

        $sudahAda = $gabungan->baris()->whereNotNull('siswa_id')->pluck('siswa_id')->all();

        $siswa = Siswa::query()
            ->where('kelas_id', $gabungan->kelas_id)
            ->aktif()
            ->whereNotIn('id', $sudahAda)
            ->orderBy('nama')
            ->get();

        foreach ($siswa as $item) {
            $gabungan->baris()->create([
                'siswa_id' => $item->id,
                'jumlah' => 0,
                'status_validasi' => PembayaranGabunganBaris::STATUS_ERROR,
                'pesan' => 'Isi jumlah pembayaran untuk siswa ini atau keluarkan dari gabungan.',
            ]);
        }

        return $siswa->count();
    }

    protected function pastikanDraft(PembayaranGabungan $gabungan): void
    {
        if (! $gabungan->isDraft()) {
            throw new \RuntimeException('Gabungan yang sudah diposting tidak dapat diubah.');
        }
    }

    protected function pastikanAdmin(): void
    {
        if (! auth()->user()?->isAdmin()) {
            throw new \RuntimeException('Hanya Administrator yang dapat melakukan void.');
        }
    }
}
