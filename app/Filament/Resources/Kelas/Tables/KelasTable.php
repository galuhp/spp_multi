<?php

namespace App\Filament\Resources\Kelas\Tables;

use App\Models\ProfilSekolah;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KelasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tahunAjaran.nama')
                    ->label('Cakupan TA')
                    ->default('Lintas Tahun')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'X', '10', '7', 'VII', '1', 'I' => 'gray',
                        'XI', '11', '8', 'VIII', '2', 'II', '3', 'III', '4', 'IV', '5', 'V' => 'info',
                        'XII', '12', '9', 'IX', '6', 'VI' => 'success',
                        default => 'primary',
                    })
                    ->sortable(),
                TextColumn::make('nama_kelas')
                    ->label('Nama Rombel Kelas')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('jurusan.nama')
                    ->label('Kompetensi Keahlian')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswas_count')
                    ->counts('siswas')
                    ->label('Jumlah Siswa')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tahun_ajaran_id')
                    ->label('Filter Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama'),
                SelectFilter::make('tingkat')
                    ->label('Filter Tingkat')
                    ->options(fn (): array => ProfilSekolah::tingkatKelasOptions()),
                SelectFilter::make('jurusan_id')
                    ->label('Filter Jurusan')
                    ->relationship('jurusan', 'nama'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                ]),
            ]);
    }
}
