<?php

namespace App\Filament\Resources\TahunAjarans;

use App\Filament\Resources\TahunAjarans\Pages\CreateTahunAjaran;
use App\Filament\Resources\TahunAjarans\Pages\EditTahunAjaran;
use App\Filament\Resources\TahunAjarans\Pages\ListTahunAjarans;
use App\Filament\Resources\TahunAjarans\Schemas\TahunAjaranForm;
use App\Filament\Resources\TahunAjarans\Tables\TahunAjaransTable;
use App\Models\RolePermission;
use App\Models\TahunAjaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TahunAjaranResource extends Resource
{
    protected static ?string $model = TahunAjaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Tahun Ajaran';

    protected static ?string $modelLabel = 'Tahun Ajaran';

    protected static ?string $pluralModelLabel = 'Tahun Ajaran';

    protected static ?int $navigationSort = 0;

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
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::MASTER_DATA);
    }

    public static function form(Schema $schema): Schema
    {
        return TahunAjaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahunAjaransTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTahunAjarans::route('/'),
            'create' => CreateTahunAjaran::route('/create'),
            'edit' => EditTahunAjaran::route('/{record}/edit'),
        ];
    }
}
