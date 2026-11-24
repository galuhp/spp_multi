<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\TenantAnnouncementResource\Pages;
use App\Models\TenantAnnouncement;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TenantAnnouncementResource extends Resource
{
    protected static ?string $model = TenantAnnouncement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Pengumuman Tenant';

    protected static ?string $modelLabel = 'Pengumuman';

    protected static ?string $pluralModelLabel = 'Pengumuman Tenant';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul Pengumuman')
                    ->required()
                    ->maxLength(150),

                Select::make('type')
                    ->label('Tipe Banner')
                    ->options([
                        'info' => 'Informasi',
                        'success' => 'Pembaruan / Sukses',
                        'warning' => 'Peringatan',
                        'danger' => 'Penting / Darurat',
                    ])
                    ->default('info')
                    ->required(),

                Textarea::make('message')
                    ->label('Isi Pengumuman')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Select::make('target_type')
                    ->label('Target Sekolah')
                    ->options([
                        'all' => 'Semua Sekolah',
                        'selected' => 'Sekolah Tertentu',
                    ])
                    ->default('all')
                    ->required()
                    ->live(),

                Select::make('tenants')
                    ->label('Pilih Sekolah')
                    ->relationship('tenants', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => $get('target_type') === 'selected')
                    ->required(fn (Get $get): bool => $get('target_type') === 'selected'),

                DateTimePicker::make('starts_at')
                    ->label('Mulai Tampil (Opsional)')
                    ->seconds(false),

                DateTimePicker::make('ends_at')
                    ->label('Selesai Tampil (Opsional)')
                    ->seconds(false)
                    ->afterOrEqual('starts_at'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'danger' => 'danger',
                        default => 'info',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'success' => 'Pembaruan',
                        'warning' => 'Peringatan',
                        'danger' => 'Darurat',
                        default => 'Informasi',
                    }),

                TextColumn::make('target_type')
                    ->label('Target')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'all' ? 'primary' : 'gray')
                    ->formatStateUsing(fn (string $state): string => $state === 'all' ? 'Semua Sekolah' : 'Sekolah Tertentu'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Langsung'),

                TextColumn::make('ends_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Tanpa batas'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'info' => 'Informasi',
                        'success' => 'Pembaruan',
                        'warning' => 'Peringatan',
                        'danger' => 'Darurat',
                    ]),
                SelectFilter::make('target_type')
                    ->label('Target')
                    ->options([
                        'all' => 'Semua Sekolah',
                        'selected' => 'Sekolah Tertentu',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantAnnouncements::route('/'),
            'create' => Pages\CreateTenantAnnouncement::route('/create'),
            'edit' => Pages\EditTenantAnnouncement::route('/{record}/edit'),
        ];
    }
}
