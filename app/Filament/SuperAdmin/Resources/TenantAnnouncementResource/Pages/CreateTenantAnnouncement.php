<?php

namespace App\Filament\SuperAdmin\Resources\TenantAnnouncementResource\Pages;

use App\Filament\SuperAdmin\Resources\TenantAnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantAnnouncement extends CreateRecord
{
    protected static string $resource = TenantAnnouncementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
