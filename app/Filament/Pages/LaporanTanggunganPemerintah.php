<?php

namespace App\Filament\Pages;

use App\Models\Kelas;
use App\Models\RolePermission;
use App\Models\SiswaTanggunganPemerintah;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class LaporanTanggunganPemerintah extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Ditanggung Pemerintah';

    protected static ?string $title = 'Laporan Ditanggung Pemerintah';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.laporan-tanggungan-pemerintah';

    public string $status = 'aktif';

    public ?string $kelas_id = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::LAPORAN);
    }

    public function resetFilter(): void
    {
        $this->status = 'aktif';
        $this->kelas_id = null;
    }

    /**
     * @return Collection<int, SiswaTanggunganPemerintah>
     */
    public function getRowsProperty(): Collection
    {
        return SiswaTanggunganPemerintah::query()
            ->with(['siswa.kelas.jurusan', 'siswa.spp', 'spp'])
            ->when($this->status === 'aktif', fn ($query) => $query->whereNull('dicabut_at'))
            ->when($this->status === 'dicabut', fn ($query) => $query->whereNotNull('dicabut_at'))
            ->when($this->kelas_id, fn ($query) => $query->whereHas(
                'siswa',
                fn ($query) => $query->where('kelas_id', $this->kelas_id),
            ))
            ->get()
            ->sortBy(fn (SiswaTanggunganPemerintah $row): string => $row->siswa?->nama ?? '')
            ->values();
    }

    public function getTotalNominalProperty(): int
    {
        return (int) $this->rows->sum(fn (SiswaTanggunganPemerintah $row): int => $row->nominal());
    }

    /**
     * @return array<int, string>
     */
    public function getKelasOptionsProperty(): array
    {
        return Kelas::query()
            ->with('jurusan')
            ->orderBy('nama_kelas')
            ->get()
            ->mapWithKeys(fn (Kelas $kelas): array => [
                $kelas->id => $kelas->nama_kelas.($kelas->jurusan?->kode ? ' — '.$kelas->jurusan->kode : ''),
            ])
            ->all();
    }
}
