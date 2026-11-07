<?php

namespace App\Filament\Pages;

use App\Models\TahunAjaran;
use App\Support\ImporMaster\ImporMasterService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\WithFileUploads;
use UnitEnum;

class ImporDataMaster extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Impor Data Master';

    protected static ?string $title = 'Impor Data Master (Tarif, Kelas, Jurusan)';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.impor-data-master';

    public string $tabAktif = 'tarif';

    public mixed $berkasTarif = null;

    public mixed $berkasKelas = null;

    public mixed $berkasJurusan = null;

    public ?int $tahun_ajaran_id = null;

    /**
     * @var array{berhasil: int, baru: int, diperbarui: int, gagal: int, errors: array<int, string>}|null
     */
    public ?array $hasilTarif = null;

    /**
     * @var array{berhasil: int, baru: int, diperbarui: int, gagal: int, errors: array<int, string>}|null
     */
    public ?array $hasilKelas = null;

    /**
     * @var array{berhasil: int, baru: int, diperbarui: int, gagal: int, errors: array<int, string>}|null
     */
    public ?array $hasilJurusan = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->tahun_ajaran_id = TahunAjaran::where('aktif', true)->value('id')
            ?? TahunAjaran::orderByDesc('nama')->value('id');
    }

    public function setTab(string $tab): void
    {
        $this->tabAktif = $tab;
    }

    public function prosesImporTarif(): void
    {
        $this->validate([
            'berkasTarif' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'berkasTarif.required' => 'Pilih berkas spreadsheet tarif terlebih dahulu.',
            'berkasTarif.mimes' => 'Format berkas harus .xlsx, .xls, atau .csv.',
            'berkasTarif.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        try {
            $path = $this->berkasTarif->getRealPath();
            $service = app(ImporMasterService::class);
            $this->hasilTarif = $service->imporTarif($path);

            if ($this->hasilTarif['berhasil'] > 0) {
                Notification::make()
                    ->title('Impor Tarif Berhasil')
                    ->body("Berhasil memproses {$this->hasilTarif['berhasil']} tarif ({$this->hasilTarif['baru']} baru, {$this->hasilTarif['diperbarui']} diperbarui).")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Impor Tarif Gagal')
                    ->body('Tidak ada data tarif valid yang dapat diproses.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Memproses Berkas')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->berkasTarif = null;
    }

    public function prosesImporKelas(): void
    {
        $this->validate([
            'berkasKelas' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
            'tahun_ajaran_id' => ['nullable', 'integer', 'exists:tahun_ajarans,id'],
        ], [
            'berkasKelas.required' => 'Pilih berkas spreadsheet kelas terlebih dahulu.',
            'berkasKelas.mimes' => 'Format berkas harus .xlsx, .xls, atau .csv.',
            'berkasKelas.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        try {
            $path = $this->berkasKelas->getRealPath();
            $service = app(ImporMasterService::class);
            $this->hasilKelas = $service->imporKelas($path, $this->tahun_ajaran_id);

            if ($this->hasilKelas['berhasil'] > 0) {
                Notification::make()
                    ->title('Impor Kelas Berhasil')
                    ->body("Berhasil memproses {$this->hasilKelas['berhasil']} kelas ({$this->hasilKelas['baru']} baru, {$this->hasilKelas['diperbarui']} diperbarui).")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Impor Kelas Gagal')
                    ->body('Tidak ada data kelas valid yang dapat diproses.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Memproses Berkas')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->berkasKelas = null;
    }

    public function prosesImporJurusan(): void
    {
        $this->validate([
            'berkasJurusan' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'berkasJurusan.required' => 'Pilih berkas spreadsheet jurusan terlebih dahulu.',
            'berkasJurusan.mimes' => 'Format berkas harus .xlsx, .xls, atau .csv.',
            'berkasJurusan.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        try {
            $path = $this->berkasJurusan->getRealPath();
            $service = app(ImporMasterService::class);
            $this->hasilJurusan = $service->imporJurusan($path);

            if ($this->hasilJurusan['berhasil'] > 0) {
                Notification::make()
                    ->title('Impor Jurusan Berhasil')
                    ->body("Berhasil memproses {$this->hasilJurusan['berhasil']} jurusan ({$this->hasilJurusan['baru']} baru, {$this->hasilJurusan['diperbarui']} diperbarui).")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Impor Jurusan Gagal')
                    ->body('Tidak ada data jurusan valid yang dapat diproses.')
                    ->danger()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Memproses Berkas')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->berkasJurusan = null;
    }
}
