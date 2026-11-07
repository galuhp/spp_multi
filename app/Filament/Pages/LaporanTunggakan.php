<?php

namespace App\Filament\Pages;

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

class LaporanTunggakan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Tunggakan';

    protected static ?string $title = 'Laporan Tunggakan SPP & Non-SPP per Siswa & Kelas';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.laporan-tunggakan';

    public ?string $tahun_ajaran_id = null;

    public ?string $kelas_id = null;

    public ?string $tingkat = null;

    public string $status = 'menunggak';

    public string $urutkan = 'kelas';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->isWaliKelas()) {
            return true;
        }

        return $user->isStaff() && (
            RolePermission::allowed($user->role, RolePermission::LAPORAN)
            || RolePermission::allowed($user->role, RolePermission::LAPORAN_TUNGGAKAN)
        );
    }

    public function mount(): void
    {
        $user = auth()->user();
        $service = app(LaporanSppService::class);

        $this->tahun_ajaran_id = request()->filled('tahun_ajaran_id')
            ? (string) request()->query('tahun_ajaran_id')
            : (string) ($service->tahunAjaranAktif()?->id ?? '');

        $this->kelas_id = request()->filled('kelas_id')
            ? (string) request()->query('kelas_id')
            : null;

        $this->tingkat = request()->filled('tingkat')
            ? (string) request()->query('tingkat')
            : null;

        $status = (string) request()->query('status', 'menunggak');
        $this->status = array_key_exists($status, $this->getStatusListProperty()) ? $status : 'menunggak';

        $urutkan = (string) request()->query('urutkan', 'kelas');
        $this->urutkan = array_key_exists($urutkan, $this->getUrutkanListProperty()) ? $urutkan : 'kelas';
    }

    public function updatedTahunAjaranId(): void
    {
        $this->kelas_id = null;
    }

    public function resetFilter(): void
    {
        $this->tahun_ajaran_id = (string) (app(LaporanSppService::class)->tahunAjaranAktif()?->id ?? '');
        $this->kelas_id = null;
        $this->tingkat = null;
        $this->status = 'menunggak';
        $this->urutkan = 'kelas';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak_per_kelas')
                ->label('Cetak Semua per Kelas')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->url(fn (): string => route('laporan.tunggakan.cetak', array_filter([
                    'tahun_ajaran_id' => $this->tahun_ajaran_id,
                    'kelas_id' => $this->kelas_id,
                    'tingkat' => $this->tingkat,
                    'status' => $this->status,
                    'urutkan' => $this->urutkan,
                    'mode' => 'per_kelas',
                ], fn ($nilai): bool => filled($nilai))))
                ->openUrlInNewTab(),

            Action::make('cetak')
                ->label('Cetak Rekap Laporan')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('laporan.tunggakan.cetak', array_filter([
                    'tahun_ajaran_id' => $this->tahun_ajaran_id,
                    'kelas_id' => $this->kelas_id,
                    'tingkat' => $this->tingkat,
                    'status' => $this->status,
                    'urutkan' => $this->urutkan,
                ], fn ($nilai): bool => filled($nilai))))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return array{tahun_ajaran: TahunAjaran|null, bulan: array<int, array{bulan: string, tahun: int}>, siswa: Collection<int, array<string, mixed>>, per_kelas: Collection<int, array<string, mixed>>, ringkasan: array<string, int|float>}
     */
    public function getLaporanProperty(): array
    {
        return app(LaporanSppService::class)->tunggakan([
            'tahun_ajaran_id' => $this->tahun_ajaran_id,
            'kelas_id' => $this->kelas_id,
            'tingkat' => $this->tingkat,
            'status' => $this->status,
            'urutkan' => $this->urutkan,
        ]);
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
     * @return array<string, string>
     */
    public function getTingkatListProperty(): array
    {
        return \App\Models\ProfilSekolah::tingkatSppOptions();
    }

    /**
     * @return array<string, string>
     */
    public function getStatusListProperty(): array
    {
        return [
            'menunggak' => 'Hanya Menunggak',
            'lunas' => 'Sudah Lunas',
            'tanpa_tarif' => 'Tanpa Tarif',
            'semua' => 'Semua Siswa',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getUrutkanListProperty(): array
    {
        return [
            'kelas' => 'Kelas & Nama Siswa',
            'tunggakan' => 'Tunggakan Terbesar',
        ];
    }
}
