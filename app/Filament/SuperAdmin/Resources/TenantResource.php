<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TenantResource\Pages;
use App\Models\Tenant;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Sekolah (Tenant)';

    protected static ?string $modelLabel = 'Sekolah';

    protected static ?string $pluralModelLabel = 'Sekolah';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Sekolah')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, ?string $old): void {
                        if (blank($state)) {
                            return;
                        }
                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('slug')
                    ->label('Slug URL')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Digunakan pada URL: /admin/{slug}'),
                TextInput::make('domain')
                    ->label('Custom Domain (Opsional)')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('fee_per_transaction')
                    ->label('Fee per Transaksi (Rp)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('Rp')
                    ->helperText('Biaya platform per transaksi pembayaran berhasil.'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Sekolah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('login_url')
                    ->label('URL Login Sekolah')
                    ->state(fn (Tenant $record): string => url("/admin/{$record->slug}"))
                    ->url(fn (Tenant $record): string => url("/admin/{$record->slug}"), shouldOpenInNewTab: true)
                    ->color('primary')
                    ->copyable(),
                TextColumn::make('users_count')
                    ->label('Jumlah Pengguna')
                    ->counts('users')
                    ->alignEnd(),
                TextColumn::make('fee_per_transaction')
                    ->label('Fee / Transaksi')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('pembayarans_count')
                    ->label('Total Transaksi')
                    ->counts('pembayarans')
                    ->alignEnd(),
                TextColumn::make('total_fee')
                    ->label('Total Fee')
                    ->state(fn (Tenant $record): string => 'Rp ' . number_format((int) $record->fee_per_transaction * (int) $record->pembayarans_count, 0, ',', '.'))
                    ->alignEnd(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
