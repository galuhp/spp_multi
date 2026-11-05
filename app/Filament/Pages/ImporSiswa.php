<?php

namespace App\Filament\Pages;

use App\Models\ImportSiswaStaging;
use App\Models\RolePermission;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Support\ImporSiswa\ImporSiswaService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\WithFileUploads;
use Throwable;
use UnitEnum;

class ImporSiswa extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?string $navigationLabel = 'Impor Siswa';

    protected static ?string $title = 'Impor Siswa Massal';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.impor-siswa';

    public ?int $tahun_ajaran_id = null;

    public ?int $spp_id = null;

    public mixed $berkas = null;

    public bool $perbarui = false;

    public ?string $batchId = null;

    public string $filter = 'semua';

    public int $halaman = 1;

    /**
     * @var array<string, int>|null
     */
    public ?array $ringkasan = null;

    public ?string $galat = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::KESISWAAN);
    }

    public function mount(): void
    {
        $this->tahun_ajaran_id = TahunAjaran::query()->where('aktif', true)->value('id')
            ?? TahunAjaran::query()->orderByDesc('nama')->value('id');
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
    public function getSppOptionsProperty(): array
    {
        $tahunAjaran = $this->tahun_ajaran_id
            ? TahunAjaran::query()->find($this->tahun_ajaran_id)
            : null;

        if ($tahunAjaran !== null) {
            $opsi = Spp::query()
                ->where('kategori', Spp::KATEGORI_SPP)
                ->where('tahun_ajaran', $tahunAjaran->nama)
                ->orderBy('tahun')
                ->get();

            if ($opsi->isNotEmpty()) {
                return $opsi
                    ->unique('tahun_ajaran')
                    ->mapWithKeys(fn (Spp $spp): array => [
                        $spp->id => "TA {$spp->tahun_ajaran} - {$spp->formattedNominal()}",
                    ])
                    ->all();
            }
        }

        return Spp::query()
            ->where('kategori', Spp::KATEGORI_SPP)
            ->orderBy('tahun_ajaran')
            ->get()
            ->unique('tahun_ajaran')
            ->mapWithKeys(fn (Spp $spp): array => [
                $spp->id => "TA {$spp->tahun_ajaran} - {$spp->formattedNominal()}",
            ])
            ->all();
    }

    public function updatedTahunAjaranId(): void
    {
        $this->spp_id = null;
    }

    public function unggah(): void
    {
        $this->galat = null;
        $this->ringkasan = null;

        $this->validate([
            'tahun_ajaran_id' => ['required', 'integer', 'exists:tahun_ajarans,id'],
            'spp_id' => ['required', 'integer', 'exists:spps,id'],
            'berkas' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ], [
            'tahun_ajaran_id.required' => 'Pilih tahun ajaran tujuan.',
            'spp_id.required' => 'Pilih tarif SPP untuk siswa baru.',
            'berkas.required' => 'Pilih berkas Excel yang akan diimpor.',
            'berkas.mimes' => 'Berkas harus berformat .xlsx.',
            'berkas.max' => 'Ukuran berkas maksimal 5 MB.',
        ]);

        $service = app(ImporSiswaService::class);
        $batchBaru = null;

        try {
            $batchBaru = $service->mulaiBatch(
                $this->berkas->getRealPath(),
                (int) $this->tahun_ajaran_id,
                (int) $this->spp_id,
                auth()->id(),
                $this->berkas->getClientOriginalName(),
            );

            $service->validasiBatch($batchBaru, $this->perbarui);

            $this->batchId = $batchBaru;
            $this->filter = 'semua';
            $this->halaman = 1;
        } catch (Throwable $exception) {
            if ($batchBaru !== null) {
                $service->hapusBatch($batchBaru);
            }

            $this->galat = $exception->getMessage();
        } finally {
            $this->berkas = null;
        }
    }

    public function simpan(): void
    {
        if ($this->batchId === null) {
            return;
        }

        $this->galat = null;

        try {
            $this->ringkasan = app(ImporSiswaService::class)->simpanBatch($this->batchId, $this->perbarui);
            $this->batchId = null;
        } catch (Throwable $exception) {
            $this->galat = $exception->getMessage();
        }
    }

    public function batalkan(): void
    {
        if ($this->batchId !== null) {
            app(ImporSiswaService::class)->hapusBatch($this->batchId);
        }

        $this->batchId = null;
        $this->ringkasan = null;
        $this->galat = null;
        $this->filter = 'semua';
        $this->halaman = 1;
    }

    public function gantiFilter(string $filter): void
    {
        if (! in_array($filter, ['semua', 'valid', 'peringatan', 'error'], true)) {
            return;
        }

        $this->filter = $filter;
        $this->halaman = 1;
    }

    public function keHalaman(int $halaman): void
    {
        $this->halaman = max(1, min($halaman, $this->totalHalaman));
    }

    /**
     * @return array<string, int>
     */
    public function getRingkasanBatchProperty(): array
    {
        if ($this->batchId === null) {
            return [];
        }

        return app(ImporSiswaService::class)->ringkasanBatch($this->batchId);
    }

    /**
     * @return Collection<int, ImportSiswaStaging>
     */
    public function getPratinjauProperty(): Collection
    {
        if ($this->batchId === null) {
            return collect();
        }

        return ImportSiswaStaging::query()
            ->where('batch_id', $this->batchId)
            ->when($this->filter !== 'semua', fn ($query) => $query->where('status', $this->filter))
            ->orderBy('baris')
            ->forPage($this->halaman, 100)
            ->get();
    }

    public function getJumlahPratinjauProperty(): int
    {
        if ($this->batchId === null) {
            return 0;
        }

        return ImportSiswaStaging::query()
            ->where('batch_id', $this->batchId)
            ->when($this->filter !== 'semua', fn ($query) => $query->where('status', $this->filter))
            ->count();
    }

    public function getTotalHalamanProperty(): int
    {
        return max(1, (int) ceil($this->jumlahPratinjau / 100));
    }
}
