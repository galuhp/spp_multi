<?php

namespace App\Filament\Resources\Jurusans;

use App\Filament\Resources\Jurusans\Pages\CreateJurusan;
use App\Filament\Resources\Jurusans\Pages\EditJurusan;
use App\Filament\Resources\Jurusans\Pages\ListJurusans;
use App\Filament\Resources\Jurusans\RelationManagers\KelasRelationManager;
use App\Filament\Resources\Jurusans\Schemas\JurusanForm;
use App\Filament\Resources\Jurusans\Tables\JurusansTable;
use App\Models\Jurusan;
use App\Models\RolePermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class JurusanResource extends Resource
{
    protected static ?string $model = Jurusan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Kompetensi Keahlian';

    protected static ?string $modelLabel = 'Kompetensi Keahlian';

    protected static ?string $pluralModelLabel = 'Kompetensi Keahlian';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::MASTER_DATA);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::MASTER_DATA);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::MASTER_DATA);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return JurusanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JurusansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            KelasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJurusans::route('/'),
            'create' => CreateJurusan::route('/create'),
            'edit' => EditJurusan::route('/{record}/edit'),
        ];
    }
}
