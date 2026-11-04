<?php

namespace App\Filament\Resources\Spps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SPP' => 'info',
                        'Uang Gedung' => 'warning',
                        'Uang Seragam' => 'success',
                        'Biaya Praktik' => 'danger',
                        default => 'primary',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Nama Pos')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'bulanan' ? 'Bulanan' : 'Sekali Bayar')
                    ->color(fn (string $state): string => $state === 'bulanan' ? 'purple' : 'emerald'),
                TextColumn::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nominal')
                    ->label('Nominal Tarif')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format($state, 0, ',', '.'))
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('siswas_count')
                    ->counts('siswas')
                    ->label('Siswa Terkait')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->options([
                        'SPP' => 'SPP',
                        'Uang Gedung' => 'Uang Gedung',
                        'Uang Seragam' => 'Uang Seragam',
                        'Biaya Praktik' => 'Biaya Praktik',
                        'Biaya Ujian' => 'Biaya Ujian',
                    ]),
                SelectFilter::make('tipe')
                    ->options([
                        'bulanan' => 'Bulanan',
                        'sekali_bayar' => 'Sekali Bayar',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
