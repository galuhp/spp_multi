<?php

namespace App\Filament\Resources\PembayaranGabungans\Pages;

use App\Filament\Resources\PembayaranGabungans\PembayaranGabunganResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPembayaranGabungans extends ListRecords
{
    protected static string $resource = PembayaranGabunganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Buat Gabungan'),
        ];
    }
}
