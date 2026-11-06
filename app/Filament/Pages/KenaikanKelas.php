<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\ProsesKelasBatch;
use App\Models\RiwayatKelas;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\KenaikanKelas\ProsesKelasService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class KenaikanKelas extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?string $navigationLabel = 'Kenaikan Kelas';

    protected static ?string $title = 'Kenaikan Kelas Massal';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.kenaikan-kelas';

    public ?int $tahun_ajaran_asal_id = null;

    public ?int $tahun_ajaran_tujuan_id = null;

    public ?int $kelas_asal_id = null;

    /**
     * @var array<int, array{status: string, kelas_tujuan_id: ?int}>
     */
    public array $keputusan = [];

    public bool $pratinjau = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $hasil = null;

    public ?string $galat = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::KESISWAAN);
    }

    public function mount(): void
    {
        $this->tahun_ajaran_asal_id = TahunAjaran::query()->where('aktif', true)->value('id')
            ?? TahunAjaran::query()->orderByDesc('nama')->value('id');

        $this->tahun_ajaran_tujuan_id = $this->tebakTahunAjaranTujuan($this->tahun_ajaran_asal_id);
    }

    /**
     * @return array<int, string>
     */
    public function getTahunAjaranOptionsProperty(): array
    {
        return TahunAjaran::query()->orderByDesc('nama')->pluck('nama', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    public function getTahunAjaranTujuanOptionsProperty(): array
    {
        return TahunAjaran::query()
            ->when(
                $this->tahun_ajaran_asal_id,
                fn ($query) => $query->whereKeyNot($this->tahun_ajaran_asal_id),
            )
            ->orderByDesc('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getKelasAsalOptionsProperty(): array
    {
        if (! $this->tahun_ajaran_asal_id) {
            return [];
        }

        return Kelas::query()
            ->with('jurusan')
            ->where('tahun_ajaran_id', $this->tahun_ajaran_asal_id)
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get()
            ->mapWithKeys(fn (Kelas $kelas): array => [
                $kelas->id => "{$kelas->nama_kelas} ({$kelas->tingkat}) - {$kelas->jurusan?->nama}",
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function getKelasTujuanOptionsProperty(): array
    {
        if (! $this->tahun_ajaran_tujuan_id) {
            return [];
        }

        return Kelas::query()
            ->with('jurusan')
            ->where('tahun_ajaran_id', $this->tahun_ajaran_tujuan_id)
            ->orderBy('tingkat')
            ->orderBy('nama_kelas')
            ->get()
            ->mapWithKeys(fn (Kelas $kelas): array => [
                $kelas->id => "{$kelas->nama_kelas} ({$kelas->tingkat}) - {$kelas->jurusan?->kode}",
            ])
            ->all();
    }

    /**
     * @return Collection<int, array{siswa: Siswa, sudah_ada: bool, tingkat_akhir: bool, default_kelas_id: ?int}>
     */
    public function getSiswaProperty(): Collection
    {
        if (! $this->kelas_asal_id || ! $this->tahun_ajaran_tujuan_id || $this->tahun_ajaran_asal_id === $this->tahun_ajaran_tujuan_id) {
            return collect();
        }

        $kelas = Kelas::query()->with('jurusan')->find($this->kelas_asal_id);

        if (! $kelas) {
            return collect();
        }

        $service = app(ProsesKelasService::class);
        $default = $service->kelasTujuanDefault($kelas, $this->tahun_ajaran_tujuan_id);
        $tingkatAkhir = in_array($service->normalisasiTingkat($kelas->tingkat), ProsesKelasService::TINGKAT_AKHIR, true);

        $siswaList = $service->siswaKelas($this->kelas_asal_id);
        $sudahAdaIds = $siswaList->isEmpty()
            ? []
            : array_flip(
                RiwayatKelas::query()
                    ->where('tahun_ajaran_id', $this->tahun_ajaran_tujuan_id)
                    ->whereIn('siswa_id', $siswaList->pluck('id')->all())
                    ->pluck('siswa_id')
                    ->all(),
            );

        return $siswaList->map(function ($siswa) use ($default, $tingkatAkhir, $sudahAdaIds): array {
            return [
                'siswa' => $siswa,
                'sudah_ada' => isset($sudahAdaIds[$siswa->id]),
                'tingkat_akhir' => $tingkatAkhir,
                'default_kelas_id' => $default?->id,
            ];
        });
    }

    public function updatedTahunAjaranAsalId(): void
    {
        $this->kelas_asal_id = null;
        $this->keputusan = [];
        $this->pratinjau = false;
        $this->hasil = null;
        $this->galat = null;

        if (! $this->tahun_ajaran_tujuan_id || $this->tahun_ajaran_tujuan_id === $this->tahun_ajaran_asal_id) {
            $this->tahun_ajaran_tujuan_id = $this->tebakTahunAjaranTujuan($this->tahun_ajaran_asal_id);
        }
    }

    public function updatedTahunAjaranTujuanId(): void
    {
        $this->keputusan = [];
        $this->pratinjau = false;
        $this->hasil = null;
        $this->galat = null;

        if ($this->tahun_ajaran_asal_id && $this->tahun_ajaran_tujuan_id === $this->tahun_ajaran_asal_id) {
            $this->tahun_ajaran_tujuan_id = null;
            $this->galat = 'Tahun ajaran tujuan tidak boleh sama dengan tahun ajaran asal.';
        }
    }

    public function updatedKelasAsalId(): void
    {
        $this->inisialisasiKeputusan();
    }

    /**
     * Adjust the target class automatically when the status changes.
     */
    public function updatedKeputusan(mixed $value, string $key): void
    {
        [$siswaId, $field] = array_pad(explode('.', $key), 2, null);

        if ($field !== 'status' || ! is_numeric($siswaId)) {
            return;
        }

        $this->sesuaikanKelasTujuan((int) $siswaId, (string) $value);
    }

    protected function sesuaikanKelasTujuan(int $siswaId, string $status): void
    {
        $kelas = $this->kelas_asal_id ? Kelas::query()->find($this->kelas_asal_id) : null;

        if (! $kelas || ! $this->tahun_ajaran_tujuan_id || $this->tahun_ajaran_asal_id === $this->tahun_ajaran_tujuan_id) {
            return;
        }

        $service = app(ProsesKelasService::class);
        $tingkat = $status === RiwayatKelas::STATUS_TINGGAL
            ? $service->normalisasiTingkat($kelas->tingkat)
            : $service->tingkatBerikutnya($service->normalisasiTingkat($kelas->tingkat));

        if ($tingkat === null) {
            return;
        }

        $kandidat = Kelas::query()
            ->where('tahun_ajaran_id', $this->tahun_ajaran_tujuan_id)
            ->where('jurusan_id', $kelas->jurusan_id)
            ->whereIn('tingkat', [$tingkat, $service->formatTingkat($tingkat)])
            ->get();

        if ($kandidat->count() === 1) {
            $this->keputusan[$siswaId]['kelas_tujuan_id'] = $kandidat->first()->id;
        }
    }

    public function inisialisasiKeputusan(): void
    {
        $this->keputusan = [];
        $this->pratinjau = false;
        $this->hasil = null;
        $this->galat = null;

        $kelas = $this->kelas_asal_id ? Kelas::query()->find($this->kelas_asal_id) : null;

        if (! $kelas || ! $this->tahun_ajaran_tujuan_id || $this->tahun_ajaran_asal_id === $this->tahun_ajaran_tujuan_id) {
            return;
        }

        $service = app(ProsesKelasService::class);
        $tingkatAkhir = in_array($service->normalisasiTingkat($kelas->tingkat), ProsesKelasService::TINGKAT_AKHIR, true);

        if ($tingkatAkhir) {
            $this->galat = 'Kelas ini berada di tingkat akhir. Gunakan fitur Kelulusan untuk memproses siswa.';

            return;
        }

        $default = $service->kelasTujuanDefault($kelas, $this->tahun_ajaran_tujuan_id);

        foreach ($service->siswaKelas($this->kelas_asal_id) as $siswa) {
            $this->keputusan[$siswa->id] = [
                'status' => RiwayatKelas::STATUS_NAIK,
                'kelas_tujuan_id' => $default?->id,
            ];
        }
    }

    public function pratinjau(): void
    {
        $this->galat = null;

        if (! $this->tahun_ajaran_asal_id || ! $this->tahun_ajaran_tujuan_id || ! $this->kelas_asal_id) {
            $this->galat = 'Lengkapi tahun ajaran asal, tahun ajaran tujuan, dan kelas asal terlebih dahulu.';

            return;
        }

        if ($this->tahun_ajaran_asal_id === $this->tahun_ajaran_tujuan_id) {
            $this->galat = 'Tahun ajaran tujuan tidak boleh sama dengan tahun ajaran asal.';

            return;
        }

        if ($this->keputusan === []) {
            $this->galat = 'Tidak ada siswa pada kelas ini.';

            return;
        }

        foreach ($this->keputusan as $siswaId => $baris) {
            if (blank($baris['kelas_tujuan_id'] ?? null)) {
                $this->galat = 'Pilih kelas tujuan untuk setiap siswa.';

                return;
            }
        }

        $this->pratinjau = true;
    }

    public function eksekusi(): void
    {
        if (! auth()->user()?->isStaff()) {
            abort(403);
        }

        if (! $this->pratinjau) {
            $this->galat = 'Lakukan pratinjau sebelum menyimpan.';

            return;
        }

        try {
            $hasil = app(ProsesKelasService::class)->naikKelas(
                (int) $this->tahun_ajaran_asal_id,
                (int) $this->tahun_ajaran_tujuan_id,
                (int) $this->kelas_asal_id,
                $this->keputusan,
                (int) auth()->id(),
            );

            $this->hasil = [
                'batch_id' => $hasil['batch']?->id,
                'diproses' => $hasil['diproses'],
                'dilewati' => $hasil['dilewati'],
                'pesan' => $hasil['pesan'],
            ];

            $this->pratinjau = false;

            Notification::make()
                ->title('Kenaikan kelas selesai')
                ->body("{$hasil['diproses']} siswa diproses.")
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            $this->galat = $exception->getMessage();
        }
    }

    public function batalkan(int $batchId): void
    {
        if (! auth()->user()?->isStaff()) {
            abort(403);
        }

        $batch = ProsesKelasBatch::query()->findOrFail($batchId);
        $hasil = app(ProsesKelasService::class)->batalkan($batch);

        if ($hasil['berhasil']) {
            Notification::make()->title('Batch dibatalkan')->success()->send();

            return;
        }

        Notification::make()->title('Batch tidak dapat dibatalkan')->body($hasil['alasan'])->danger()->send();
    }

    /**
     * @return Collection<int, ProsesKelasBatch>
     */
    public function getBatchProperty(): Collection
    {
        return ProsesKelasBatch::query()
            ->with(['tahunAjaranAsal', 'tahunAjaranTujuan', 'pembuat'])
            ->where('jenis', ProsesKelasBatch::JENIS_NAIK_KELAS)
            ->latest('id')
            ->limit(10)
            ->get();
    }

    protected function tebakTahunAjaranTujuan(?int $asalId): ?int
    {
        if (! $asalId) {
            return null;
        }

        return TahunAjaran::query()
            ->whereKeyNot($asalId)
            ->orderBy('nama')
            ->where('nama', '>', TahunAjaran::query()->whereKey($asalId)->value('nama'))
            ->value('id');
    }
}
