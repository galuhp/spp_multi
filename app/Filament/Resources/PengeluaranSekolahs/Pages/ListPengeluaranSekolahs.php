<?php

namespace App\Filament\Resources\PengeluaranSekolahs\Pages;

use App\Filament\Resources\PengeluaranSekolahs\PengeluaranSekolahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPengeluaranSekolahs extends ListRecords
{
    protected static string $resource = PengeluaranSekolahResource::class;

    protected static ?string $title = 'Transaksi Pengeluaran Operasional Sekolah';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Catat Pengeluaran Baru')
                ->icon('heroicon-o-plus'),
        ];
    }
}
