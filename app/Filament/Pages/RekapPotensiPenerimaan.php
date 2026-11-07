<?php

namespace App\Filament\Pages;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\RolePermission;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Support\Laporan\LaporanSppService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class RekapPotensiPenerimaan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Rekap Potensi Penerimaan';

    protected static ?string $title = 'Rekap Penerimaan vs Potensi SPP & Non-SPP per Kelas/Jurusan/Tingkat';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.rekap-potensi-penerimaan';

    public ?string $tahun_ajaran_id = null;

    public string $kelompok = LaporanSppService::KELOMPOK_KELAS;

    public ?string $tingkat = null;

    public ?string $jurusan_id = null;

    public ?string $kelas_id = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::LAPORAN);
    }

    public function mount(): void
    {
        $service = app(LaporanSppService::class);

        $this->tahun_ajaran_id = request()->filled('tahun_ajaran_id')
            ? (string) request()->query('tahun_ajaran_id')
            : (string) ($service->tahunAjaranAktif()?->id ?? '');

        $kelompok = (string) request()->query('kelompok', LaporanSppService::KELOMPOK_KELAS);
        $this->kelompok = array_key_exists($kelompok, $this->getKelompokListProperty())
            ? $kelompok
            : LaporanSppService::KELOMPOK_KELAS;

        $this->tingkat = request()->filled('tingkat')
            ? (string) request()->query('tingkat')
            : null;

        $this->jurusan_id = request()->filled('jurusan_id')
            ? (string) request()->query('jurusan_id')
            : null;

        $this->kelas_id = request()->filled('kelas_id')
            ? (string) request()->query('kelas_id')
            : null;
    }

    public function updatedTahunAjaranId(): void
    {
        $this->kelas_id = null;
    }

    public function resetFilter(): void
    {
        $this->tahun_ajaran_id = (string) (app(LaporanSppService::class)->tahunAjaranAktif()?->id ?? '');
        $this->kelompok = LaporanSppService::KELOMPOK_KELAS;
        $this->tingkat = null;
        $this->jurusan_id = null;
        $this->kelas_id = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak Laporan / PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('laporan.potensi.cetak', array_filter([
                    'tahun_ajaran_id' => $this->tahun_ajaran_id,
                    'kelompok' => $this->kelompok,
                    'tingkat' => $this->tingkat,
                    'jurusan_id' => $this->jurusan_id,
                    'kelas_id' => $this->kelas_id,
                ], fn ($nilai): bool => filled($nilai))))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return array{tahun_ajaran: TahunAjaran|null, bulan: array<int, array{bulan: string, tahun: int}>, kelompok: string, baris: Collection<int, array<string, mixed>>, ringkasan: array<string, int|float>}
     */
    public function getRekapProperty(): array
    {
        return app(LaporanSppService::class)->rekapPotensi([
            'tahun_ajaran_id' => $this->tahun_ajaran_id,
            'kelas_id' => $this->kelas_id,
            'tingkat' => $this->tingkat,
            'jurusan_id' => $this->jurusan_id,
        ], $this->kelompok);
    }

    /**
     * @return Collection<int, TahunAjaran>
     */
    public function getTahunAjaranListProperty(): Collection
    {
        return TahunAjaran::query()->orderByDesc('nama')->get();
    }

    /**
     * @return Collection<int, Kelas>
     */
    public function getKelasListProperty(): Collection
    {
        if (blank($this->tahun_ajaran_id)) {
            return collect();
        }

        return Kelas::query()
            ->with('jurusan')
            ->where(function ($query): void {
                $query->where('tahun_ajaran_id', $this->tahun_ajaran_id)
                    ->orWhereNull('tahun_ajaran_id');
            })
            ->orderBy('nama_kelas')
            ->get();
    }

    /**
     * @return Collection<int, Jurusan>
     */
    public function getJurusanListProperty(): Collection
    {
        return Jurusan::query()->orderBy('nama')->get();
    }

    /**
     * @return array<string, string>
     */
    public function getKelompokListProperty(): array
    {
        return [
            LaporanSppService::KELOMPOK_KELAS => 'Per Kelas',
            LaporanSppService::KELOMPOK_JURUSAN => 'Per Jurusan',
            LaporanSppService::KELOMPOK_TINGKAT => 'Per Tingkat',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getTingkatListProperty(): array
    {
        return Spp::TINGKAT_LIST;
    }
}
