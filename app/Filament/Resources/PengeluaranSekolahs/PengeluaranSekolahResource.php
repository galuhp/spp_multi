<?php

namespace App\Filament\Resources\PengeluaranSekolahs;

use App\Filament\Resources\PengeluaranSekolahs\Pages\CreatePengeluaranSekolah;
use App\Filament\Resources\PengeluaranSekolahs\Pages\EditPengeluaranSekolah;
use App\Filament\Resources\PengeluaranSekolahs\Pages\ListPengeluaranSekolahs;
use App\Filament\Resources\PengeluaranSekolahs\Schemas\PengeluaranSekolahForm;
use App\Filament\Resources\PengeluaranSekolahs\Tables\PengeluaranSekolahsTable;
use App\Models\PengeluaranSekolah;
use App\Models\RolePermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PengeluaranSekolahResource extends Resource
{
    protected static ?string $model = PengeluaranSekolah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi SPP';

    protected static ?string $navigationLabel = 'Pengeluaran Operasional';

    protected static ?string $modelLabel = 'Pengeluaran Operasional';

    protected static ?string $pluralModelLabel = 'Pengeluaran Operasional';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP);
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'tahunAjaran']);
    }

    public static function form(Schema $schema): Schema
    {
        return PengeluaranSekolahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengeluaranSekolahsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPengeluaranSekolahs::route('/'),
            'create' => CreatePengeluaranSekolah::route('/create'),
            'edit' => EditPengeluaranSekolah::route('/{record}/edit'),
        ];
    }
}
