<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\TenantInvoice;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UnpaidInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Tagihan Belum Lunas';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TenantInvoice::query()
                    ->where('status', '!=', 'paid')
                    ->where('total_amount', '>', 0)
                    ->latest('period')
            )
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Sekolah')
                    ->searchable(),
                TextColumn::make('period')
                    ->label('Periode'),
                TextColumn::make('transaction_count')
                    ->label('Transaksi')
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->alignEnd(),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y'),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Tandai Lunas')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (TenantInvoice $record): void {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    }),
            ]);
    }
}
