<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Enums\UserRole;
use App\Filament\SuperAdmin\Resources\TenantResource\Pages;
use App\Models\LogAktivitas;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Sekolah (Tenant)';

    protected static ?string $modelLabel = 'Sekolah';

    protected static ?string $pluralModelLabel = 'Sekolah';

    public static function form(Schema $schema): Schema
    {
        $defaultStartYear = now()->month >= 7 ? now()->year : now()->year - 1;
        $defaultAcademicYear = "{$defaultStartYear}/".($defaultStartYear + 1);

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Sekolah')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, ?string $old): void {
                        if (blank($state)) {
                            return;
                        }
                        $set('slug', Str::slug($state));
                    }),
                TextInput::make('slug')
                    ->label('Slug URL')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Digunakan pada URL: /admin/{slug}'),
                TextInput::make('domain')
                    ->label('Custom Domain (Opsional)')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('fee_per_transaction')
                    ->label('Fee per Transaksi (Rp)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->prefix('Rp')
                    ->helperText('Biaya platform per transaksi pembayaran berhasil.'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Toggle::make('settings.disable_user_creation')
                    ->label('Nonaktifkan Pembuatan User Baru (Disable User Creation)')
                    ->helperText('Jika aktif, admin sekolah ini tidak dapat menambah user baru.'),
                Toggle::make('settings.disable_edit_profil_sekolah')
                    ->label('Nonaktifkan Edit Profil Sekolah (Disable Edit Profil)')
                    ->helperText('Jika aktif, admin sekolah ini tidak dapat mengakses/mengubah halaman Profil & Kop Sekolah.'),

                Section::make('Paket & Kuota Sekolah')
                    ->description('Atur paket fitur (Basic/Pro) dan batas kuota siswa khusus untuk sekolah ini.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('settings.tier')
                            ->label('Paket Fitur (Tier)')
                            ->options([
                                'basic' => 'Basic',
                                'pro' => 'Pro',
                            ])
                            ->placeholder('Ikuti Default Sistem')
                            ->helperText('Kosongkan untuk mengikuti konfigurasi default aplikasi.'),

                        TextInput::make('settings.max_siswa')
                            ->label('Kuota Maksimal Siswa')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Isi 0 atau kosongkan untuk tanpa batas.'),
                    ]),

                Section::make('Onboarding Sekolah Otomatis')
                    ->description('Buat akun Admin Sekolah pertama dan Tahun Ajaran awal sekaligus agar sekolah langsung siap digunakan.')
                    ->visibleOn('create')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Toggle::make('create_initial_admin')
                            ->label('Buat Akun Admin Sekolah Pertama')
                            ->default(true)
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('admin_name')
                            ->label('Nama Admin Sekolah')
                            ->maxLength(100)
                            ->visible(fn (Get $get): bool => (bool) $get('create_initial_admin'))
                            ->required(fn (Get $get): bool => (bool) $get('create_initial_admin')),

                        TextInput::make('admin_username')
                            ->label('Username Login Admin')
                            ->maxLength(50)
                            ->unique(User::class, 'username')
                            ->visible(fn (Get $get): bool => (bool) $get('create_initial_admin'))
                            ->required(fn (Get $get): bool => (bool) $get('create_initial_admin')),

                        TextInput::make('admin_email')
                            ->label('Email Admin (Opsional)')
                            ->email()
                            ->maxLength(100)
                            ->unique(User::class, 'email')
                            ->visible(fn (Get $get): bool => (bool) $get('create_initial_admin')),

                        TextInput::make('admin_password')
                            ->label('Kata Sandi Awal')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => (bool) $get('create_initial_admin'))
                            ->required(fn (Get $get): bool => (bool) $get('create_initial_admin')),

                        Toggle::make('create_initial_tahun_ajaran')
                            ->label('Buat Tahun Ajaran Awal Aktif')
                            ->default(true)
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('tahun_ajaran_nama')
                            ->label('Nama Tahun Ajaran')
                            ->default($defaultAcademicYear)
                            ->maxLength(50)
                            ->visible(fn (Get $get): bool => (bool) $get('create_initial_tahun_ajaran'))
                            ->required(fn (Get $get): bool => (bool) $get('create_initial_tahun_ajaran')),

                        DatePicker::make('tahun_ajaran_mulai')
                            ->label('Tanggal Mulai')
                            ->default("{$defaultStartYear}-07-01")
                            ->visible(fn (Get $get): bool => (bool) $get('create_initial_tahun_ajaran'))
                            ->required(fn (Get $get): bool => (bool) $get('create_initial_tahun_ajaran')),

                        DatePicker::make('tahun_ajaran_selesai')
                            ->label('Tanggal Selesai')
                            ->default(($defaultStartYear + 1).'-06-30')
                            ->afterOrEqual('tahun_ajaran_mulai')
                            ->visible(fn (Get $get): bool => (bool) $get('create_initial_tahun_ajaran'))
                            ->required(fn (Get $get): bool => (bool) $get('create_initial_tahun_ajaran')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Sekolah')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('login_url')
                    ->label('URL Login Sekolah')
                    ->state(fn (Tenant $record): string => url("/admin/{$record->slug}"))
                    ->url(fn (Tenant $record): string => url("/admin/{$record->slug}"), shouldOpenInNewTab: true)
                    ->color('primary')
                    ->copyable(),
                TextColumn::make('users_count')
                    ->label('Jumlah Pengguna')
                    ->counts('users')
                    ->alignEnd(),
                TextColumn::make('fee_per_transaction')
                    ->label('Fee / Transaksi')
                    ->formatStateUsing(fn ($state): string => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('pembayarans_count')
                    ->label('Total Transaksi')
                    ->counts('pembayarans')
                    ->alignEnd(),
                TextColumn::make('total_fee')
                    ->label('Total Fee')
                    ->state(fn (Tenant $record): string => 'Rp ' . number_format((int) $record->fee_per_transaction * (int) $record->pembayarans_count, 0, ',', '.'))
                    ->alignEnd(),
                TextColumn::make('tier')
                    ->label('Paket')
                    ->badge()
                    ->state(fn (Tenant $record): string => match ($record->configuredTier()) {
                        'pro' => 'Pro',
                        'basic' => 'Basic',
                        default => 'Default',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Pro' => 'primary',
                        'Basic' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('impersonate')
                    ->label('Masuk sebagai Admin')
                    ->icon(Heroicon::OutlinedArrowRightOnRectangle)
                    ->color('warning')
                    ->visible(fn (Tenant $record): bool => $record->is_active && $record->users()->where('is_super_admin', false)->exists())
                    ->schema(fn (Tenant $record): array => [
                        Select::make('user_id')
                            ->label('Pilih Akun Sekolah')
                            ->options(
                                $record->users()
                                    ->where('is_super_admin', false)
                                    ->get()
                                    ->mapWithKeys(fn (User $user): array => [
                                        $user->id => "{$user->name} ({$user->username} — {$user->role->label()})",
                                    ])
                                    ->all()
                            )
                            ->default(
                                $record->users()
                                    ->where('is_super_admin', false)
                                    ->wherePivot('role', UserRole::Admin->value)
                                    ->value('users.id')
                                ?? $record->users()
                                    ->where('is_super_admin', false)
                                    ->value('users.id')
                            )
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function (array $data, Tenant $record) {
                        $superAdmin = Auth::guard('web')->user();

                        if (! $superAdmin instanceof User || ! $superAdmin->isSuperAdmin()) {
                            abort(403);
                        }

                        $targetUser = $record->users()
                            ->where('is_super_admin', false)
                            ->findOrFail($data['user_id']);

                        $session = request()->session();
                        $session->put('impersonator_id', $superAdmin->id);
                        $session->put('impersonator_tenant_id', $record->id);
                        $session->put('impersonator_return_url', static::getUrl('index'));

                        try {
                            TenantContext::set($record);
                            LogAktivitas::catat(
                                'tenant.impersonate',
                                "SuperAdmin {$superAdmin->name} masuk sebagai {$targetUser->name} ({$targetUser->username})",
                                $targetUser,
                                ['impersonator_id' => $superAdmin->id, 'tenant_id' => $record->id],
                            );
                        } finally {
                            TenantContext::clear();
                        }

                        Auth::guard('web')->login($targetUser);
                        $session->put('password_hash_web', $targetUser->getAuthPassword());
                        $session->regenerate();

                        return redirect()->to(Filament::getPanel('admin')->getUrl($record));
                    }),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\SuperAdmin\Resources\TenantResource\RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
