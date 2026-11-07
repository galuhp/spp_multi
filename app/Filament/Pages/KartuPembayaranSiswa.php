<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\RolePermission;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Support\Laporan\LaporanSppService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class KartuPembayaranSiswa extends Page
{
    /**
     * Sementara dinonaktifkan. Ubah ke true untuk mengaktifkan kembali.
     */
    public const AKTIF = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Kartu Pembayaran Siswa';

    protected static ?string $title = 'Kartu Pembayaran Siswa (Ledger)';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.kartu-pembayaran-siswa';

    public ?string $tahun_ajaran_id = null;

    public ?string $kelas_id = null;

    public ?string $siswa_id = null;

    public static function canAccess(): bool
    {
        if (! self::AKTIF) {
            return false;
        }

        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::LAPORAN);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::AKTIF && parent::shouldRegisterNavigation();
    }

    public function mount(): void
    {
        $service = app(LaporanSppService::class);

        $this->tahun_ajaran_id = request()->filled('tahun_ajaran_id')
            ? (string) request()->query('tahun_ajaran_id')
            : (string) ($service->tahunAjaranAktif()?->id ?? '');

        $this->kelas_id = request()->filled('kelas_id')
            ? (string) request()->query('kelas_id')
            : null;

        $this->siswa_id = request()->filled('siswa_id')
            ? (string) request()->query('siswa_id')
            : null;
    }

    public function updatedTahunAjaranId(): void
    {
        $this->kelas_id = null;
        $this->siswa_id = null;
    }

    public function updatedKelasId(): void
    {
        $this->siswa_id = null;
    }

    public function resetFilter(): void
    {
        $this->tahun_ajaran_id = (string) (app(LaporanSppService::class)->tahunAjaranAktif()?->id ?? '');
        $this->kelas_id = null;
        $this->siswa_id = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak Kartu / PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('laporan.kartu.cetak', array_filter([
                    'tahun_ajaran_id' => $this->tahun_ajaran_id,
                    'siswa_id' => $this->siswa_id,
                ], fn ($nilai): bool => filled($nilai))))
                ->disabled(fn (): bool => blank($this->siswa_id))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return array{siswa: Siswa, kelas: Kelas|null, tarif: Spp|null, tahun_ajaran: TahunAjaran, entries: Collection<int, array<string, mixed>>, void_payments: Collection<int, Pembayaran>, ringkasan: array<string, int|float>}|null
     */
    public function getKartuProperty(): ?array
    {
        if (blank($this->siswa_id) || blank($this->tahun_ajaran_id)) {
            return null;
        }

        $siswa = Siswa::query()->find($this->siswa_id);
        $tahunAjaran = TahunAjaran::query()->find($this->tahun_ajaran_id);

        if (! $siswa || ! $tahunAjaran) {
            return null;
        }

        return app(LaporanSppService::class)->kartuPembayaran($siswa, $tahunAjaran);
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
            ->where('tahun_ajaran_id', $this->tahun_ajaran_id)
            ->orderBy('nama_kelas')
            ->get();
    }

    /**
     * Students available for the selected academic year and class.
     *
     * @return Collection<int, array{siswa: Siswa, kelas: Kelas}>
     */
    public function getSiswaListProperty(): Collection
    {
        $tahunAjaran = filled($this->tahun_ajaran_id)
            ? TahunAjaran::query()->find($this->tahun_ajaran_id)
            : null;

        $siswa = app(LaporanSppService::class)->siswaDenganKelas($tahunAjaran);

        if (filled($this->kelas_id)) {
            $siswa = $siswa->filter(
                fn (array $item): bool => (int) $item['kelas']->id === (int) $this->kelas_id,
            );
        }

        return $siswa->values();
    }
}
