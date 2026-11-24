<?php

namespace App\Filament\SuperAdmin\Resources\TenantAnnouncementResource\Pages;

use App\Filament\SuperAdmin\Resources\TenantAnnouncementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantAnnouncements extends ListRecords
{
    protected static string $resource = TenantAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
