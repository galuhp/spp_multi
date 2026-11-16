<?php

namespace App\Filament\SuperAdmin\Resources\TenantInvoiceResource\Pages;

use App\Filament\SuperAdmin\Resources\TenantInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenantInvoice extends EditRecord
{
    protected static string $resource = TenantInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
