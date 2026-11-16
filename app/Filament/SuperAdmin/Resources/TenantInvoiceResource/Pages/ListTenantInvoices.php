<?php

namespace App\Filament\SuperAdmin\Resources\TenantInvoiceResource\Pages;

use App\Filament\SuperAdmin\Resources\TenantInvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListTenantInvoices extends ListRecords
{
    protected static string $resource = TenantInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateInvoices')
                ->label('Generate Tagihan Bulanan')
                ->color('primary')
                ->form([
                    TextInput::make('period')
                        ->label('Periode (YYYY-MM)')
                        ->default(now()->subMonth()->format('Y-m'))
                        ->required(),
                ])
                ->action(function (array $data): void {
                    Artisan::call('tenants:generate-invoices', [
                        '--period' => $data['period'],
                    ]);

                    Notification::make()
                        ->title("Tagihan periode {$data['period']} berhasil di-generate.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
