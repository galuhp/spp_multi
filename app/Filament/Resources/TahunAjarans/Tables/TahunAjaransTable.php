<?php

namespace App\Filament\Resources\TahunAjarans\Tables;

use App\Models\TahunAjaran;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TahunAjaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('kelas_count')
                    ->counts('kelas')
                    ->label('Rombel Kelas')
                    ->sortable(),
                IconColumn::make('aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('aktif')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->disabled(fn (TahunAjaran $record): bool => $record->sedangDipakai())
                    ->tooltip(fn (TahunAjaran $record): ?string => $record->sedangDipakai()
                        ? 'Tidak dapat dihapus karena masih dipakai kelas atau riwayat kelas.'
                        : null),
            ]);
    }
}
