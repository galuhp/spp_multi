<?php

namespace App\Filament\Resources\Kelas\Pages;

use App\Filament\Resources\Kelas\KelasResource;
use App\Models\TahunAjaran;
use App\Support\ImporMaster\ImporMasterService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListKelas extends ListRecords
{
    protected static string $resource = KelasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('imporKelas')
                ->label('Impor Kelas')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->modalHeading('Impor Data Kelas (Excel / CSV)')
                ->modalDescription(new HtmlString('Unggah file spreadsheet data kelas (nama_kelas, tingkat, kode_jurusan, tahun_ajaran, wali_kelas). Belum punya format? <a href="' . route('impor.template.kelas') . '" target="_blank" class="text-primary-600 font-semibold underline">Unduh Template Excel</a>'))
                ->modalSubmitActionLabel('Mulai Impor')
                ->form([
                    Select::make('tahun_ajaran_id')
                        ->label('Tahun Ajaran Default')
                        ->helperText('Digunakan bila kolom tahun_ajaran pada baris Excel dikosongkan')
                        ->options(TahunAjaran::query()->orderByDesc('nama')->pluck('nama', 'id')->all())
                        ->default(fn () => TahunAjaran::where('aktif', true)->value('id'))
                        ->required(),
                    FileUpload::make('berkas')
                        ->label('Berkas Spreadsheet')
                        ->helperText('Format yang didukung: .xlsx, .xls, .csv (Maksimal 5 MB)')
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
                        $defaultTaId = ! empty($data['tahun_ajaran_id']) ? (int) $data['tahun_ajaran_id'] : null;

                        $service = app(ImporMasterService::class);
                        $hasil = $service->imporKelas($path, $defaultTaId);

                        if ($hasil['berhasil'] > 0) {
                            $pesan = "Berhasil memproses {$hasil['berhasil']} kelas ({$hasil['baru']} baru, {$hasil['diperbarui']} diperbarui).";
                            if ($hasil['gagal'] > 0) {
                                $pesan .= " {$hasil['gagal']} baris dilewati.";
                            }

                            Notification::make()
                                ->title('Impor Kelas Berhasil')
                                ->body($pesan)
                                ->success()
                                ->send();
                        } else {
                            $detailError = implode("\n", array_slice($hasil['errors'], 0, 5));
                            Notification::make()
                                ->title('Impor Kelas Gagal')
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
            Action::make('unduhTemplateKelas')
                ->label('Unduh Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->url(fn (): string => route('impor.template.kelas'))
                ->openUrlInNewTab(),
        ];
    }
}
