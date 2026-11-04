<?php

namespace App\Filament\Resources\Siswas\Tables;

use App\Filament\Resources\Pembayarans\Pages\CreatePembayaran;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SiswasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nisn')
                    ->label('NISN')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nis')
                    ->label('NIS')
                    ->fontFamily('mono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nama')
                    ->label('Nama Siswa')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kelas.jurusan.kode')
                    ->label('Jurusan')
                    ->badge()
                    ->sortable(),
                TextColumn::make('spp.nominal')
                    ->label('Tarif SPP')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format($state ?? 0, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('no_telp')
                    ->label('No. Telepon')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kelas_id')
                    ->label('Filter Kelas')
                    ->relationship('kelas', 'nama_kelas'),
            ])
            ->recordActions([
                Action::make('bayar')
                    ->label('Bayar')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->url(fn ($record): string => CreatePembayaran::getUrl(['siswa_id' => $record->id])),
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
