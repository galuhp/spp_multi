<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Pengguna')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Username')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Hak Akses')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ($state?->value ?? $state) {
                        'admin' => 'Administrator',
                        'petugas' => 'Petugas TU',
                        'siswa' => 'Siswa',
                        default => (string) $state,
                    })
                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                        'admin' => 'danger',
                        'petugas' => 'info',
                        'siswa' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Filter Role')
                    ->options([
                        'admin' => 'Administrator',
                        'petugas' => 'Petugas TU',
                        'siswa' => 'Siswa',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(function (User $record): bool {
                        if ($record->id === auth()->id()) {
                            return false;
                        }

                        return array_key_exists('pembayarans_count', $record->getAttributes())
                            ? (int) $record->getAttribute('pembayarans_count') === 0
                            : ! $record->pembayarans()->exists();
                    }),
            ]);
    }
}
