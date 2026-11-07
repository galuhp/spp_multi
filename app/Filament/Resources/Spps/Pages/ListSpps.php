<?php

namespace App\Filament\Resources\Spps\Pages;

use App\Filament\Resources\Spps\SppResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListSpps extends ListRecords
{
    protected static string $resource = SppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('seedDemo')
                ->label('Reset & Seed Data Demo')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Kosongkan & Seed Ulang Data Demo')
                ->modalDescription('Tindakan ini akan mengosongkan master tarif, siswa, kelas, dan transaksi pembayaran lama, lalu menanam ulang data tarif resmi dari lampiran Excel (38 tarif) dan 161 siswa demo. Lanjutkan?')
                ->modalSubmitActionLabel('Ya, Reset & Seed Ulang')
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->action(function () {
                    Artisan::call('db:seed', ['--force' => true]);
                    Notification::make()
                        ->title('Data Demo Berhasil Direset & Diseed')
                        ->body('Master tarif, kelas, siswa, dan transaksi telah diperbarui sesuai skema terbaru.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
