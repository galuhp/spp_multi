<?php

namespace App\Filament\Resources\Spps\Pages;

use App\Filament\Resources\Spps\SppResource;
use App\Support\ImporMaster\ImporMasterService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListSpps extends ListRecords
{
    protected static string $resource = SppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('imporTarif')
                ->label('Impor Tarif')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->modalHeading('Impor Data Tarif Pembayaran (Excel / CSV)')
                ->modalDescription(new HtmlString('Unggah file spreadsheet tarif SPP & Non-SPP (kategori, nama, tipe, tahun_ajaran, tingkat, nominal, keterangan). Belum punya format? <a href="'.route('impor.template.tarif').'" target="_blank" class="text-primary-600 font-semibold underline">Unduh Template Excel</a>'))
                ->modalSubmitActionLabel('Mulai Impor')
                ->form([
                    FileUpload::make('berkas')
                        ->label('Berkas Spreadsheet')
                        ->helperText('Format yang didukung: .xlsx, .xls, .csv (Maksimal 5 MB). Kosongkan tingkat untuk tarif yang berlaku di semua tingkat.')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->maxSize(5120)
                        ->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $path = ImporMasterService::resolveUploadedFilePath($data['berkas']);
                        $service = app(ImporMasterService::class);
                        $hasil = $service->imporTarif($path);

                        if ($hasil['berhasil'] > 0) {
                            $pesan = "Berhasil memproses {$hasil['berhasil']} tarif ({$hasil['baru']} baru, {$hasil['diperbarui']} diperbarui).";
                            if ($hasil['gagal'] > 0) {
                                $pesan .= " {$hasil['gagal']} baris dilewati.";
                            }

                            Notification::make()
                                ->title('Impor Tarif Berhasil')
                                ->body($pesan)
                                ->success()
                                ->send();
                        } else {
                            $detailError = implode("\n", array_slice($hasil['errors'], 0, 5));
                            Notification::make()
                                ->title('Impor Tarif Gagal')
                                ->body($detailError ?: 'Tidak ada data valid yang dapat diimpor.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Terjadi Kesalahan Saat Mengimpor')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('unduhTemplateTarif')
                ->label('Unduh Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->url(fn (): string => route('impor.template.tarif'))
                ->openUrlInNewTab(),
        ];
    }
}
