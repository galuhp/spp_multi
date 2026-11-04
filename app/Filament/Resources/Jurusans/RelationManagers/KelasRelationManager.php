<?php

namespace App\Filament\Resources\Jurusans\RelationManagers;

use App\Models\TahunAjaran;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class KelasRelationManager extends RelationManager
{
    protected static string $relationship = 'kelas';

    protected static ?string $title = 'Rombel Kelas';

    protected static ?string $modelLabel = 'Rombel';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama')
                    ->default(fn (): ?int => TahunAjaran::query()->where('aktif', true)->value('id'))
                    ->required(),
                TextInput::make('nama_kelas')
                    ->label('Nama Rombel')
                    ->placeholder('Contoh: X RPL 1')
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                            ->where('tahun_ajaran_id', $get('tahun_ajaran_id')),
                    )
                    ->validationMessages([
                        'unique' => 'Nama kelas sudah terdaftar pada tahun ajaran ini.',
                    ])
                    ->required(),
                Select::make('tingkat')
                    ->label('Tingkat')
                    ->options([
                        'X' => 'X',
                        'XI' => 'XI',
                        'XII' => 'XII',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_kelas')
            ->columns([
                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('nama_kelas')
                    ->label('Nama Rombel')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->sortable(),
                TextColumn::make('siswas_count')
                    ->counts('siswas')
                    ->label('Jumlah Siswa')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Rombel'),
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
