<?php

namespace App\Filament\SuperAdmin\Resources\TenantAnnouncementResource\Pages;

use App\Filament\SuperAdmin\Resources\TenantAnnouncementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantAnnouncement extends EditRecord
{
    protected static string $resource = TenantAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
