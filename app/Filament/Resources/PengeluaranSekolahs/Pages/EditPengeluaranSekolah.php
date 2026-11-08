<?php

namespace App\Filament\Resources\PengeluaranSekolahs\Pages;

use App\Filament\Resources\PengeluaranSekolahs\PengeluaranSekolahResource;
use App\Models\PengeluaranSekolah;
use App\Support\Pengeluaran\PengeluaranService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPengeluaranSekolah extends EditRecord
{
    protected static string $resource = PengeluaranSekolahResource::class;

    protected static ?string $title = 'Ubah Transaksi Pengeluaran Sekolah';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('batal')
                ->label('Batal')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->modalHeading('Batalkan pengeluaran?')
                ->modalDescription('Data pengeluaran tetap tersimpan sebagai riwayat pembatalan.')
                ->visible(fn (PengeluaranSekolah $record): bool => ! $record->isVoid()
                    && (auth()->user()?->isAdmin() ?? false))
                ->form([
                    Textarea::make('alasan')
                        ->label('Alasan pembatalan')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (PengeluaranSekolah $record, array $data): void {
                    app(PengeluaranService::class)->batalkan(
                        $record,
                        (int) auth()->id(),
                        $data['alasan'],
                    );

                    Notification::make()
                        ->title('Pengeluaran dibatalkan')
                        ->body('Riwayat dan nomor bukti tetap tersimpan.')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make()
                ->visible(fn (PengeluaranSekolah $record): bool => ! $record->isVoid()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
