<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Filament\SuperAdmin\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class InactiveTenantsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Sekolah Pasif (0 Transaksi 30 Hari Terakhir)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Tenant::query()
                    ->where('is_active', true)
                    ->whereDoesntHave('pembayarans', function (Builder $query): void {
                        $query->withoutGlobalScopes()
                            ->where('created_at', '>=', now()->subDays(30));
                    })
                    ->withCount(['users', 'pembayarans'])
                    ->latest('id')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Sekolah')
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label('Pengguna')
                    ->alignEnd(),
                TextColumn::make('pembayarans_count')
                    ->label('Total Transaksi')
                    ->alignEnd(),
                TextColumn::make('created_at')
                    ->label('Terdaftar Sejak')
                    ->date('d M Y'),
            ])
            ->recordActions([
                Action::make('kelola')
                    ->label('Kelola')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (Tenant $record): string => TenantResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
