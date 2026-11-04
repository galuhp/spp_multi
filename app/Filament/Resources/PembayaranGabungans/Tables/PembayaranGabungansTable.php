<?php

namespace App\Filament\Resources\PembayaranGabungans\Tables;

use App\Models\PembayaranGabungan;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PembayaranGabungansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('sumber')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PembayaranGabungan::sumberOptions()[$state] ?? (string) $state),
                TextColumn::make('baris_count')
                    ->counts('baris')
                    ->label('Siswa')
                    ->sortable(),
                TextColumn::make('total_diterima')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PembayaranGabungan::statusOptions()[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        PembayaranGabungan::STATUS_DIPOSTING => 'success',
                        PembayaranGabungan::STATUS_VOID => 'danger',
                        PembayaranGabungan::STATUS_MEMPROSES => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('pembuat.name')
                    ->label('Petugas')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('sumber')
                    ->label('Sumber')
                    ->options(PembayaranGabungan::sumberOptions()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PembayaranGabungan::statusOptions()),
                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('dari')->label('Dari')->native(false),
                        DatePicker::make('sampai')->label('Sampai')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'] ?? null, fn (Builder $q, $tanggal): Builder => $q->whereDate('tanggal', '>=', $tanggal))
                            ->when($data['sampai'] ?? null, fn (Builder $q, $tanggal): Builder => $q->whereDate('tanggal', '<=', $tanggal));
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Kelola'),
            ])
            ->defaultSort('id', 'desc');
    }
}
