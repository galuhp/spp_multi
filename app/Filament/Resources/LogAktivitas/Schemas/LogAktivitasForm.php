<?php

namespace App\Filament\Resources\LogAktivitas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LogAktivitasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('aksi')
                    ->required(),
                TextInput::make('subjek_tipe'),
                TextInput::make('subjek_id')
                    ->numeric(),
                TextInput::make('deskripsi')
                    ->required(),
                Textarea::make('data')
                    ->columnSpanFull(),
                TextInput::make('ip_address'),
            ]);
    }
}
