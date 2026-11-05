<?php

namespace App\Filament\Pages;

use App\Models\Siswa;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;
use UnitEnum;

class RiwayatPembayaranSiswa extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static UnitEnum|string|null $navigationGroup = 'Portal Siswa';

    protected static ?string $navigationLabel = 'Riwayat Pembayaran';

    protected static ?string $title = 'Riwayat Pembayaran SPP';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.riwayat-pembayaran-siswa';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSiswa() ?? false;
    }

    public function mount(): void
    {
        abort_unless($this->siswa, 404, 'Data siswa untuk akun ini tidak ditemukan.');
    }

    public function getSiswaProperty(): ?Siswa
    {
        $user = auth()->user();

        $siswa = $user?->siswa
            ?? ($user ? Siswa::where('nisn', $user->username)->first() : null);

        return $siswa?->loadMissing(['kelas.jurusan']);
    }

    public function getPembayaransProperty(): LengthAwarePaginator
    {
        $siswa = $this->siswa;

        if (! $siswa) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        }

        return $siswa->pembayarans()
            ->with(['spp', 'user'])
            ->latest('tgl_bayar')
            ->paginate(15);
    }

    public function getTotalDibayarProperty(): int
    {
        return $this->siswa?->totalDibayar() ?? 0;
    }
}
