<?php

namespace App\Filament\Resources\PembayaranGabungans\Pages;

use App\Filament\Resources\PembayaranGabungans\PembayaranGabunganResource;
use App\Models\PembayaranGabungan;
use App\Models\PembayaranGabunganBaris;
use App\Models\Siswa;
use App\Support\ImporSiswa\PembacaNilai;
use App\Support\PembayaranMassal\PembayaranMassalService;
use App\Support\PembayaranMassal\TransferBankImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class EditPembayaranGabungan extends EditRecord
{
    protected static string $resource = PembayaranGabunganResource::class;

    public function getTitle(): string
    {
        return 'Kelola Pembayaran Kolektif';
    }

    protected function getHeaderActions(): array
    {
        $service = app(PembayaranMassalService::class);

        return [
            Action::make('muatSiswaKelas')
                ->label('Muat Siswa Kelas')
                ->icon('heroicon-o-users')
                ->color('gray')
                ->visible(fn (): bool => $this->record->isDraft()
                    && $this->record->sumber === PembayaranGabungan::SUMBER_SETORAN_KELAS
                    && $this->record->kelas_id)
                ->requiresConfirmation()
                ->action(function (): void {
                    $jumlah = app(PembayaranMassalService::class)->muatSiswaKelas($this->record);

                    Notification::make()
                        ->title('Siswa kelas dimuat')
                        ->body("{$jumlah} siswa ditambahkan. Isi jumlah atau keluarkan siswa yang tidak ikut.")
                        ->success()
                        ->send();

                    $this->record->refresh();
                }),

            Action::make('posting')
                ->label('Posting')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->isDraft())
                ->disabled(function () use ($service): bool {
                    return ! $service->cekPosting($this->record)['boleh'];
                })
                ->tooltip(function () use ($service): ?string {
                    return $service->cekPosting($this->record)['alasan'];
                })
                ->action(fn () => $this->posting()),

            Action::make('void')
                ->label('Void Gabungan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->isPosted() && (auth()->user()?->isAdmin() ?? false))
                ->form([
                    Textarea::make('alasan')
                        ->label('Alasan void')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(fn (array $data) => $this->voidGabungan($data['alasan'])),

            Action::make('kuitansi')
                ->label('Cetak Kuitansi')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->visible(fn (): bool => $this->record->isPosted())
                ->url(fn (): string => route('pembayaran.gabungan.kuitansi', $this->record))
                ->openUrlInNewTab(),

            Action::make('rekap')
                ->label('Cetak Rekap')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => route('pembayaran.gabungan.rekap', $this->record))
                ->openUrlInNewTab(),

            Action::make('imporTransfer')
                ->label('Impor Transfer Bank')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->visible(fn (): bool => $this->record->isDraft()
                    && $this->record->sumber === PembayaranGabungan::SUMBER_TRANSFER_BANK)
                ->form([
                    FileUpload::make('berkas')
                        ->label('Berkas Excel (.xlsx)')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->required(),
                ])
                ->action(fn (array $data) => $this->imporTransfer($data['berkas'])),

            Action::make('templateTransfer')
                ->label('Unduh Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (): bool => $this->record->sumber === PembayaranGabungan::SUMBER_TRANSFER_BANK)
                ->url(route('pembayaran.gabungan.template'))
                ->openUrlInNewTab(),

            Action::make('laporanError')
                ->label('Unduh Laporan Error')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->baris()
                    ->where('status_validasi', PembayaranGabunganBaris::STATUS_ERROR)
                    ->exists())
                ->url(fn (): string => route('pembayaran.gabungan.error', $this->record))
                ->openUrlInNewTab(),
        ];
    }

    protected function getFormActions(): array
    {
        if (! $this->record->isDraft()) {
            return [];
        }

        return parent::getFormActions();
    }

    public function posting(): void
    {
        $hasil = app(PembayaranMassalService::class)->posting($this->record, (int) auth()->id());

        if ($hasil['berhasil']) {
            $this->record->refresh();

            Notification::make()
                ->title('Gabungan diposting')
                ->body('Pembayaran per siswa berhasil dibuat.')
                ->success()
                ->send();

            $this->redirect(static::getUrl(['record' => $this->record]));

            return;
        }

        Notification::make()
            ->title('Posting gagal')
            ->body($hasil['pesan'])
            ->danger()
            ->send();

        $this->record->refresh();
    }

    public function voidGabungan(string $alasan): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        $hasil = app(PembayaranMassalService::class)->voidGabungan($this->record, (int) auth()->id(), $alasan);

        if ($hasil['berhasil']) {
            $this->record->refresh();

            Notification::make()
                ->title('Gabungan di-void')
                ->body('Pembayaran terkait ditandai void. Nomor kuitansi tetap tersimpan.')
                ->success()
                ->send();

            $this->redirect(static::getUrl(['record' => $this->record]));

            return;
        }

        Notification::make()
            ->title('Void gagal')
            ->body($hasil['pesan'])
            ->danger()
            ->send();
    }

    public function imporTransfer(mixed $berkas): void
    {
        if (! $this->record->isDraft()) {
            return;
        }

        if (is_array($berkas)) {
            $berkas = reset($berkas);
        }

        $path = $berkas instanceof TemporaryUploadedFile
            ? $berkas->getRealPath()
            : (is_file((string) $berkas) ? (string) $berkas : storage_path('app/public/'.ltrim((string) $berkas, '/')));

        $service = app(PembayaranMassalService::class);
        $berhasil = 0;
        $gagal = 0;

        $rows = Excel::toCollection(new TransferBankImport, $path)->first() ?? collect();

        $rows->chunk(500)->each(function ($chunk) use ($service, &$berhasil, &$gagal): void {
            foreach ($chunk as $row) {
                $nis = PembacaNilai::teks($row['nis'] ?? null);
                $jumlah = (int) preg_replace('/\D/', '', (string) ($row['jumlah'] ?? 0));
                $referensi = trim((string) ($row['referensi'] ?? '')) ?: null;

                $siswa = $nis
                    ? Siswa::query()
                        ->where(fn ($query) => $query->where('nis', $nis)->orWhere('nisn', $nis))
                        ->first()
                    : null;

                if (! $siswa || $siswa->is_archived || $siswa->status !== 'aktif') {
                    $this->record->baris()->create([
                        'siswa_id' => null,
                        'jumlah' => $jumlah,
                        'referensi' => $referensi ?: 'NIS '.($nis ?: '-'),
                        'status_validasi' => PembayaranGabunganBaris::STATUS_ERROR,
                        'pesan' => 'Siswa dengan NIS '.($nis ?: '-').' tidak ditemukan atau sudah diarsipkan.',
                    ]);
                    $gagal++;

                    continue;
                }

                try {
                    $service->tambahBaris($this->record, $siswa->id, $jumlah, $referensi);
                    $berhasil++;
                } catch (\Throwable $exception) {
                    $this->record->baris()->create([
                        'siswa_id' => $siswa->id,
                        'jumlah' => $jumlah,
                        'referensi' => $referensi,
                        'status_validasi' => PembayaranGabunganBaris::STATUS_ERROR,
                        'pesan' => $exception->getMessage(),
                    ]);
                    $gagal++;
                }
            }
        });

        $this->record->refresh();

        Notification::make()
            ->title('Impor transfer bank selesai')
            ->body("{$berhasil} baris ditambahkan, {$gagal} baris error.")
            ->success()
            ->send();
    }
}
