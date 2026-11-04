<?php

namespace App\Filament\Resources\LogAktivitas\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LogAktivitasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Pelaku')
                    ->placeholder('Sistem')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('aksi')
                    ->label('Aksi')
                    ->badge()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('deskripsi')
                    ->label('Deskripsi Aktivitas')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                // Read-only
            ])
            ->toolbarActions([
                // Read-only
            ]);
    }
}
