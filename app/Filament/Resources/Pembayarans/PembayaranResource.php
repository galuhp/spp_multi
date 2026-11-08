<?php

namespace App\Filament\Resources\Pembayarans;

use App\Filament\Resources\Pembayarans\Pages\ListPembayarans;
use App\Filament\Resources\Pembayarans\Schemas\PembayaranForm;
use App\Filament\Resources\Pembayarans\Tables\PembayaransTable;
use App\Models\Pembayaran;
use App\Models\RolePermission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PembayaranResource extends Resource
{
    protected static ?string $model = Pembayaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static UnitEnum|string|null $navigationGroup = 'Transaksi SPP';

    protected static ?string $navigationLabel = 'History Pembayaran';

    protected static ?string $modelLabel = 'History Pembayaran';

    protected static ?string $pluralModelLabel = 'History Pembayaran';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isStaff() && RolePermission::allowed($user->role, RolePermission::TRANSAKSI_SPP);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Keep the history menu inactive while other pages are open.
     *
     * @return array<int, string>
     */
    public static function getNavigationItemActiveRoutePattern(): string|array
    {
        return [static::getRouteBaseName().'.index'];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['siswa.kelas.jurusan', 'spp', 'user']);
        $user = auth()->user();

        if ($user && $user->isSiswa() && $user->siswa) {
            $query->where('siswa_id', $user->siswa->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return PembayaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PembayaransTable::configure($table);
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
            'index' => ListPembayarans::route('/'),
        ];
    }
}
