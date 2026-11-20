<?php

namespace App\Filament\SuperAdmin\Resources\TenantResource\RelationManagers;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Pengguna (Admin & Staf Sekolah)';

    protected static ?string $modelLabel = 'Pengguna';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(100),

                TextInput::make('username')
                    ->label('Username / ID Login')
                    ->required()
                    ->unique(User::class, 'username', ignoreRecord: true)
                    ->maxLength(50),

                TextInput::make('email')
                    ->label('Alamat Email')
                    ->email()
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->maxLength(100),

                Select::make('role')
                    ->label('Hak Akses (Role)')
                    ->options([
                        UserRole::Admin->value => 'Administrator',
                        UserRole::Petugas->value => 'Petugas TU / Kasir',
                        UserRole::WaliKelas->value => 'Wali Kelas',
                        UserRole::Siswa->value => 'Siswa',
                    ])
                    ->default(UserRole::Admin->value)
                    ->required(),

                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(255)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Minimal 8 karakter. Kosongkan jika tidak ingin mengubah kata sandi.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn (UserRole|string|null $state): string => match ($state instanceof UserRole ? $state->value : $state) {
                        UserRole::Admin->value => 'Administrator',
                        UserRole::Petugas->value => 'Petugas TU / Kasir',
                        UserRole::WaliKelas->value => 'Wali Kelas',
                        UserRole::Siswa->value => 'Siswa',
                        default => (string) $state,
                    })
                    ->color(fn (UserRole|string|null $state): string => match ($state instanceof UserRole ? $state->value : $state) {
                        UserRole::Admin->value => 'primary',
                        UserRole::Petugas->value => 'success',
                        UserRole::WaliKelas->value => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Buat User Admin / Staf')
                    ->using(function (array $data, RelationManager $livewire): User {
                        $user = User::create([
                            'name' => $data['name'],
                            'username' => $data['username'],
                            'email' => $data['email'] ?? null,
                            'role' => $data['role'],
                            'password' => $data['password'],
                            'email_verified_at' => now(),
                        ]);

                        $livewire->getOwnerRecord()->users()->attach($user->id, [
                            'role' => $data['role'],
                        ]);

                        return $user;
                    }),
                AttachAction::make()
                    ->label('Tautkan User Existing')
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->label('Role di Sekolah Ini')
                            ->options([
                                UserRole::Admin->value => 'Administrator',
                                UserRole::Petugas->value => 'Petugas TU / Kasir',
                                UserRole::WaliKelas->value => 'Wali Kelas',
                                UserRole::Siswa->value => 'Siswa',
                            ])
                            ->default(UserRole::Admin->value)
                            ->required(),
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(function (User $record, array $data, RelationManager $livewire): void {
                        if (isset($data['role'])) {
                            $livewire->getOwnerRecord()->users()->updateExistingPivot($record->id, [
                                'role' => $data['role'],
                            ]);
                        }
                    }),
                DetachAction::make()
                    ->label('Lepas'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
