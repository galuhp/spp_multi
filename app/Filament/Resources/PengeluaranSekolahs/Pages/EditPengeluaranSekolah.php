<?php

namespace App\Filament\Resources\PengeluaranSekolahs\Pages;

use App\Filament\Resources\PengeluaranSekolahs\PengeluaranSekolahResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPengeluaranSekolah extends EditRecord
{
    protected static string $resource = PengeluaranSekolahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
