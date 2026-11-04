<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Spp;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class LaporanPembayaran extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi SPP';

    protected static ?string $navigationLabel = 'Laporan Pembayaran';

    protected static ?string $title = 'Laporan & Rekapitulasi Pembayaran';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.laporan-pembayaran';

    public ?string $tgl_awal = null;

    public ?string $tgl_akhir = null;

    public ?string $kategori = null;

    public ?string $kelas_id = null;

    public ?string $metode_pembayaran = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isStaff() ?? false;
    }

    public function mount(): void
    {
        $this->tgl_awal = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tgl_akhir = Carbon::now()->format('Y-m-d');
    }

    public function resetFilter(): void
    {
        $this->tgl_awal = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->tgl_akhir = Carbon::now()->format('Y-m-d');
        $this->kategori = null;
        $this->kelas_id = null;
        $this->metode_pembayaran = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')
                ->label('Cetak Laporan / PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('primary')
                ->url(fn (): string => route('laporan.cetak', array_filter([
                    'tgl_awal' => $this->tgl_awal,
                    'tgl_akhir' => $this->tgl_akhir,
                    'kategori' => $this->kategori,
                    'kelas_id' => $this->kelas_id,
                    'metode_pembayaran' => $this->metode_pembayaran,
                ])))
                ->openUrlInNewTab(),
        ];
    }

    public function getFilteredPembayaransProperty(): Collection
    {
        return Pembayaran::with(['siswa.kelas.jurusan', 'user', 'spp'])
            ->when($this->tgl_awal, fn ($q, $tgl) => $q->whereDate('tgl_bayar', '>=', $tgl))
            ->when($this->tgl_akhir, fn ($q, $tgl) => $q->whereDate('tgl_bayar', '<=', $tgl))
            ->when($this->kelas_id, fn ($q, $id) => $q->whereHas('siswa', fn ($sq) => $sq->where('kelas_id', $id)))
            ->when($this->metode_pembayaran, fn ($q, $m) => $q->where('metode_pembayaran', $m))
            ->when($this->kategori, fn ($q, $kat) => $q->whereHas('spp', fn ($sq) => $sq->where('kategori', $kat)))
            ->orderBy('tgl_bayar', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getKelasListProperty()
    {
        return Kelas::with('jurusan')->orderBy('nama_kelas')->get();
    }

    public function getKategoriListProperty(): array
    {
        return Spp::select('kategori')->distinct()->pluck('kategori')->all();
    }
}
