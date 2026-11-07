<?php

namespace App\Filament\Pages;

use App\Models\PengeluaranSekolah;
use App\Models\RolePermission;
use App\Models\TahunAjaran;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class LaporanPengeluaran extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Pengeluaran';

    protected static ?string $title = 'Laporan & Rekapitulasi Pengeluaran Sekolah';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.laporan-pengeluaran';

    public ?string $tgl_awal = null;

    public ?string $tgl_akhir = null;

    public ?string $kategori = null;

    public ?string $metode_pembayaran = null;

    public ?string $tahun_ajaran_id = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user?->isStaff()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return RolePermission::allowed($user->role, RolePermission::LAPORAN);
    }

    public function mount(): void
    {
        $this->tgl_awal = request()->filled('tgl_awal')
            ? (string) request()->query('tgl_awal')
            : Carbon::now()->startOfMonth()->format('Y-m-d');

        $this->tgl_akhir = request()->filled('tgl_akhir')
            ? (string) request()->query('tgl_akhir')
            : Carbon::now()->format('Y-m-d');

        $this->kategori = request()->filled('kategori') ? (string) request()->query('kategori') : null;
        $this->metode_pembayaran = request()->filled('metode_pembayaran') ? (string) request()->query('metode_pembayaran') : null;
        $this->tahun_ajaran_id = request()->filled('tahun_ajaran_id') ? (string) request()->query('tahun_ajaran_id') : null;
    }

    public function resetFilter(): void
    {
        $this->tgl_awal = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tgl_akhir = Carbon::now()->format('Y-m-d');
        $this->kategori = null;
        $this->metode_pembayaran = null;
        $this->tahun_ajaran_id = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak Laporan / PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('pengeluaran.rekap.cetak', array_filter([
                    'tgl_awal' => $this->tgl_awal,
                    'tgl_akhir' => $this->tgl_akhir,
                    'kategori' => $this->kategori,
                    'metode_pembayaran' => $this->metode_pembayaran,
                    'tahun_ajaran_id' => $this->tahun_ajaran_id,
                ])))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return Collection<int, PengeluaranSekolah>
     */
    public function getFilteredPengeluaransProperty(): Collection
    {
        return PengeluaranSekolah::with(['user', 'tahunAjaran'])
            ->when($this->tgl_awal, fn ($q, $tgl) => $q->whereDate('tanggal', '>=', $tgl))
            ->when($this->tgl_akhir, fn ($q, $tgl) => $q->whereDate('tanggal', '<=', $tgl))
            ->when($this->kategori, fn ($q, $k) => $q->where('kategori', $k))
            ->when($this->metode_pembayaran, fn ($q, $m) => $q->where('metode_pembayaran', $m))
            ->when($this->tahun_ajaran_id, fn ($q, $id) => $q->where('tahun_ajaran_id', $id))
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    public function getKategoriListProperty(): array
    {
        return PengeluaranSekolah::KATEGORI_LIST;
    }

    /**
     * @return Collection<int, TahunAjaran>
     */
    public function getTahunAjaranListProperty(): Collection
    {
        return TahunAjaran::orderBy('tanggal_mulai', 'desc')->get();
    }
}
