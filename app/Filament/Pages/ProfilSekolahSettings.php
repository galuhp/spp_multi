<?php

namespace App\Filament\Pages;

use App\Models\LogAktivitas;
use App\Models\ProfilSekolah;
use App\Models\RolePermission;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ProfilSekolahSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan & Akses';

    protected static ?string $navigationLabel = 'Profil & Kop Sekolah';

    protected static ?string $title = 'Profil Sekolah & Kop Surat';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.profil-sekolah-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $tenant = \Filament\Facades\Filament::getTenant();

        if ($tenant instanceof \App\Models\Tenant && $tenant->isEditProfilSekolahDisabled()) {
            return false;
        }

        $user = auth()->user();

        return $user?->isAdmin() && RolePermission::allowed($user->role, RolePermission::PENGATURAN_AKSES);
    }

    public function mount(): void
    {
        $profil = ProfilSekolah::current();
        $data = $profil->toArray();
        $data['jenjang'] = $profil->normalizedJenjang();
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identitas Aplikasi')
                    ->description('Nama dan logo yang tampil di panel, halaman login, serta dokumen cetak.')
                    ->components([
                        Grid::make(2)
                            ->components([
                                TextInput::make('app_name')
                                    ->label('Nama Aplikasi')
                                    ->placeholder('Contoh: E-SPP SEKOLAH')
                                    ->required()
                                    ->maxLength(100),
                                FileUpload::make('logo')
                                    ->label('Logo Sekolah')
                                    ->helperText('Gunakan gambar PNG, JPG, atau WebP. Maksimal 2 MB.')
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->disk('public')
                                    ->directory('sekolah')
                                    ->visibility('public')
                                    ->maxSize(2048)
                                    ->imagePreviewHeight('96'),
                            ]),
                    ]),
                Section::make('Identitas Instansi & Dinas')
                    ->description('Digunakan untuk kop surat resmi pada kuitansi dan laporan SPP.')
                    ->components([
                        TextInput::make('pemerintah_daerah')
                            ->label('Pemerintah Daerah')
                            ->placeholder('Contoh: PEMERINTAH DAERAH PROVINSI JAWA BARAT')
                            ->required(),
                        TextInput::make('dinas_pendidikan')
                            ->label('Dinas Pendidikan')
                            ->placeholder('Contoh: DINAS PENDIDIKAN DAN KEBUDAYAAN')
                            ->required(),
                        TextInput::make('nama_sekolah')
                            ->label('Nama Sekolah')
                            ->placeholder('Contoh: SMK Negeri 1 Contoh')
                            ->required(),
                        Grid::make(2)
                            ->components([
                                TextInput::make('npsn')
                                    ->label('NPSN')
                                    ->placeholder('Nomor Pokok Sekolah Nasional')
                                    ->required(),
                                Select::make('jenjang')
                                    ->label('Jenjang Pendidikan')
                                    ->options(ProfilSekolah::JENJANG_LIST)
                                    ->helperText('Menentukan pilihan Tingkat Kelas yang muncul pada form Kelas, Tarif SPP, Kasir, dan Laporan.')
                                    ->required(),
                            ]),
                    ]),
                Section::make('Kontak & Alamat Sekolah')
                    ->components([
                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->rows(2)
                            ->required(),
                        Grid::make(2)
                            ->components([
                                TextInput::make('kota')
                                    ->label('Kota / Kabupaten')
                                    ->required(),
                                TextInput::make('kode_pos')
                                    ->label('Kode Pos'),
                                TextInput::make('no_telp')
                                    ->label('Nomor Telepon')
                                    ->tel(),
                                TextInput::make('email')
                                    ->label('Email Resmi')
                                    ->email(),
                                TextInput::make('website')
                                    ->label('Website Sekolah')
                                    ->url()
                                    ->nullable(),
                            ]),
                    ]),
                Section::make('Pejabat Penandatangan')
                    ->description('Data Kepala Sekolah untuk tanda tangan digital dan pengesahan kuitansi.')
                    ->components([
                        Grid::make(2)
                            ->components([
                                TextInput::make('nama_kepala_sekolah')
                                    ->label('Nama Kepala Sekolah')
                                    ->required(),
                                TextInput::make('nip_kepala_sekolah')
                                    ->label('NIP Kepala Sekolah'),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $profil = ProfilSekolah::current();
        $profil->update($state);
        ProfilSekolah::clearCache();

        LogAktivitas::create([
            'user_id' => auth()->id(),
            'aksi' => 'update',
            'deskripsi' => 'Memperbarui pengaturan profil & kop surat sekolah',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Notification::make()
            ->title('Profil sekolah berhasil diperbarui')
            ->success()
            ->send();
    }
}
