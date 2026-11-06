<?php

namespace App\Filament\Resources\PembayaranGabungans;

use App\Filament\Resources\PembayaranGabungans\Pages\CreatePembayaranGabungan;
use App\Filament\Resources\PembayaranGabungans\Pages\EditPembayaranGabungan;
use App\Filament\Resources\PembayaranGabungans\Pages\ListPembayaranGabungans;
use App\Filament\Resources\PembayaranGabungans\RelationManagers\BarisRelationManager;
use App\Filament\Resources\PembayaranGabungans\Schemas\PembayaranGabunganForm;
use App\Filament\Resources\PembayaranGabungans\Tables\PembayaranGabungansTable;
use App\Models\PembayaranGabungan;
use App\Models\RolePermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PembayaranGabunganResource extends Resource
{
    protected static ?string $model = PembayaranGabungan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi SPP';

    protected static ?string $navigationLabel = 'History Pembayaran Kolektif';

    protected static ?string $modelLabel = 'Pembayaran Kolektif';

    protected static ?string $pluralModelLabel = 'History Pembayaran Kolektif';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    /**
     * Keep the history menu inactive while the separate create page is open.
     *
     * @return array<int, string>
     */
    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [
            static::getRouteBaseName().'.index',
            static::getRouteBaseName().'.edit',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('pembuat')
            ->withCount('baris');
    }

    public static function form(Schema $schema): Schema
    {
        return PembayaranGabunganForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PembayaranGabungansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            BarisRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPembayaranGabungans::route('/'),
            'create' => CreatePembayaranGabungan::route('/create'),
            'edit' => EditPembayaranGabungan::route('/{record}/edit'),
        ];
    }
}
