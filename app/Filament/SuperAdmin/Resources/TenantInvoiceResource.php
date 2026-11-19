<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TenantInvoiceResource\Pages;
use App\Models\TenantInvoice;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenantInvoiceResource extends Resource
{
    protected static ?string $model = TenantInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static ?string $navigationLabel = 'Tagihan Bulanan';

    protected static ?string $modelLabel = 'Tagihan';

    protected static ?string $pluralModelLabel = 'Tagihan Bulanan';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->label('Sekolah (Tenant)')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->searchable(),
                TextInput::make('period')
                    ->label('Periode (YYYY-MM)')
                    ->required()
                    ->maxLength(7)
                    ->placeholder('2026-11'),
                TextInput::make('transaction_count')
                    ->label('Jumlah Transaksi')
                    ->numeric()
                    ->required()
                    ->default(0),
                TextInput::make('fee_per_transaction')
                    ->label('Fee / Transaksi (Rp)')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->default(0),
                TextInput::make('total_amount')
                    ->label('Total Tagihan (Rp)')
                    ->numeric()
                    ->required()
                    ->prefix('Rp')
                    ->default(0),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'unpaid' => 'Belum Dibayar',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                    ])
                    ->required()
                    ->default('unpaid'),
                DatePicker::make('due_date')
                    ->label('Jatuh Tempo'),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->label('Sekolah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period')
                    ->label('Periode')
                    ->sortable(),
                TextColumn::make('transaction_count')
                    ->label('Transaksi')
                    ->numeric()
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Tagihan')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'overdue' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                        default => 'Belum Dibayar',
                    }),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Dibayar Pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'unpaid' => 'Belum Dibayar',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                    ]),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Tandai Lunas')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (TenantInvoice $record): bool => ! $record->isPaid())
                    ->requiresConfirmation()
                    ->action(function (TenantInvoice $record): void {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    }),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantInvoices::route('/'),
            'create' => Pages\CreateTenantInvoice::route('/create'),
            'edit' => Pages\EditTenantInvoice::route('/{record}/edit'),
        ];
    }
}
