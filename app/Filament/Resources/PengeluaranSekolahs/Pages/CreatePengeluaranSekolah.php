<?php

namespace App\Filament\Resources\PengeluaranSekolahs\Pages;

use App\Filament\Resources\PengeluaranSekolahs\PengeluaranSekolahResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePengeluaranSekolah extends CreateRecord
{
    protected static string $resource = PengeluaranSekolahResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
