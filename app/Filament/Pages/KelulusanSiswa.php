<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\ProsesKelasBatch;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Support\KenaikanKelas\ProsesKelasService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class KelulusanSiswa extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?string $navigationLabel = 'Kelulusan';

    protected static ?string $title = 'Kelulusan & Arsip Siswa';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.kelulusan-siswa';

    public ?int $tahun_ajaran_id = null;

    public ?int $kelas_id = null;

    /**
     * @var array<int, int>
     */
    public array $siswa_ids = [];

    public string $tanggal_lulus = '';

    public bool $pratinjau = false;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $hasil = null;

    public ?string $galat = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isStaff() ?? false;
    }

    public function mount(): void
    {
        $this->tahun_ajaran_id = TahunAjaran::query()->where('aktif', true)->value('id')
            ?? TahunAjaran::query()->orderByDesc('nama')->value('id');
        $this->tanggal_lulus = now()->format('Y-m-d');
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
    public function getKelasOptionsProperty(): array
    {
        if (! $this->tahun_ajaran_id) {
            return [];
        }

        return Kelas::query()
            ->with('jurusan')
            ->where('tahun_ajaran_id', $this->tahun_ajaran_id)
            ->whereIn('tingkat', ['12', '13', 'XII'])
            ->orderBy('nama_kelas')
            ->get()
            ->mapWithKeys(fn (Kelas $kelas): array => [
                $kelas->id => "{$kelas->nama_kelas} - {$kelas->jurusan?->nama}",
            ])
            ->all();
    }

    /**
     * @return Collection<int, array{siswa: Siswa, tunggakan: array{jumlah_bulan: int, nominal: int, bulan: array<int, string>}}>
     */
    public function getSiswaProperty(): Collection
    {
        if (! $this->kelas_id) {
            return collect();
        }

        $service = app(ProsesKelasService::class);

        return $service->siswaKelas($this->kelas_id)->map(fn ($siswa): array => [
            'siswa' => $siswa,
            'tunggakan' => $service->tunggakan($siswa),
        ]);
    }

    public function updatedTahunAjaranId(): void
    {
        $this->kelas_id = null;
        $this->siswa_ids = [];
        $this->pratinjau = false;
        $this->hasil = null;
    }

    public function updatedKelasId(): void
    {
        $this->pratinjau = false;
        $this->hasil = null;
        $this->galat = null;
        $this->siswa_ids = app(ProsesKelasService::class)
            ->siswaKelas((int) $this->kelas_id)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function pratinjau(): void
    {
        $this->galat = null;

        if (! $this->tahun_ajaran_id || ! $this->kelas_id) {
            $this->galat = 'Pilih tahun ajaran dan kelas tingkat akhir terlebih dahulu.';

            return;
        }

        if ($this->siswa_ids === []) {
            $this->galat = 'Pilih minimal satu siswa yang lulus.';

            return;
        }

        if (blank($this->tanggal_lulus)) {
            $this->galat = 'Tanggal lulus wajib diisi.';

            return;
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
            $hasil = app(ProsesKelasService::class)->luluskan(
                (int) $this->tahun_ajaran_id,
                (int) $this->kelas_id,
                array_map('intval', $this->siswa_ids),
                $this->tanggal_lulus,
                (int) auth()->id(),
            );

            $this->hasil = [
                'batch_id' => $hasil['batch']?->id,
                'diproses' => $hasil['diproses'],
                'dilewati' => $hasil['dilewati'],
            ];

            $this->pratinjau = false;

            Notification::make()
                ->title('Kelulusan selesai')
                ->body("{$hasil['diproses']} siswa diluluskan dan diarsipkan.")
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            $this->galat = $exception->getMessage();
        }
    }

    /**
     * @return Collection<int, ProsesKelasBatch>
     */
    public function getBatchProperty(): Collection
    {
        return ProsesKelasBatch::query()
            ->with(['tahunAjaranAsal', 'pembuat'])
            ->where('jenis', ProsesKelasBatch::JENIS_KELULUSAN)
            ->latest('id')
            ->limit(10)
            ->get();
    }
}
