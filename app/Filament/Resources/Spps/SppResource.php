<?php

namespace App\Filament\Resources\Spps;

use App\Filament\Resources\Spps\Pages\CreateSpp;
use App\Filament\Resources\Spps\Pages\EditSpp;
use App\Filament\Resources\Spps\Pages\ListSpps;
use App\Filament\Resources\Spps\Schemas\SppForm;
use App\Filament\Resources\Spps\Tables\SppsTable;
use App\Models\Spp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SppResource extends Resource
{
    protected static ?string $model = Spp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Tarif & Kategori';

    protected static ?string $modelLabel = 'Tarif Pembayaran';

    protected static ?string $pluralModelLabel = 'Tarif & Kategori';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return SppForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SppsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpps::route('/'),
            'create' => CreateSpp::route('/create'),
            'edit' => EditSpp::route('/{record}/edit'),
        ];
    }
}
