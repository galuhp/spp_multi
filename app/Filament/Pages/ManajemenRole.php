<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\LogAktivitas;
use App\Models\RolePermission;
use App\Models\User;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use UnitEnum;

class ManajemenRole extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan & Akses';

    protected static ?string $navigationLabel = 'Manajemen Role';

    protected static ?string $title = 'Manajemen Role & Hak Akses';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.manajemen-role';

    public string $cari = '';

    /**
     * @var array<string, array<string, bool>>
     */
    public array $akses = [];

    public function mount(): void
    {
        foreach (UserRole::cases() as $role) {
            foreach (array_keys(RolePermission::AVAILABLE) as $permission) {
                $this->akses[$role->value][$permission] = RolePermission::allowed($role, $permission);
            }
        }
    }

    public function simpanAkses(): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        $sebelum = [];

        foreach (UserRole::cases() as $role) {
            $sebelum[$role->value] = RolePermission::forRole($role);

            $dipilih = array_keys(array_filter(
                $this->akses[$role->value] ?? [],
                fn ($nilai): bool => (bool) $nilai,
            ));

            RolePermission::syncRole($role, $dipilih);
        }

        LogAktivitas::catat(
            'role_permission.diubah',
            'Hak akses per role diperbarui.',
            null,
            [
                'sebelum' => $sebelum,
                'sesudah' => $this->akses,
                'diubah_oleh' => auth()->id(),
            ]
        );

        Notification::make()
            ->title('Hak akses disimpan')
            ->body('Matriks akses role berhasil diperbarui.')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<int, array{role: UserRole, label: string, jumlah: int, deskripsi: string}>
     */
    public function getRolesProperty(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role): array => [
                'role' => $role,
                'label' => $role->label(),
                'jumlah' => User::query()->where('role', $role->value)->count(),
                'deskripsi' => match ($role) {
                    UserRole::Admin => 'Akses penuh, termasuk pengaturan sekolah dan manajemen role.',
                    UserRole::Petugas => 'Transaksi pembayaran, laporan, dan kesiswaan.',
                    UserRole::WaliKelas => 'Akses view only laporan tunggakan untuk memantau siswa di kelas binaan.',
                    UserRole::Siswa => 'Portal siswa untuk melihat tagihan dan riwayat pembayaran.',
                },
            ])
            ->all();
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersProperty(): Collection
    {
        $kataKunci = trim($this->cari);

        return User::query()
            ->when($kataKunci !== '', function ($query) use ($kataKunci): void {
                $query->where(function ($subquery) use ($kataKunci): void {
                    $subquery->where('name', 'like', "%{$kataKunci}%")
                        ->orWhere('username', 'like', "%{$kataKunci}%")
                        ->orWhere('email', 'like', "%{$kataKunci}%");
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->limit(100)
            ->get();
    }

    public function ubahRole(int $userId, string $role): void
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        validator(
            ['role' => $role],
            ['role' => ['required', Rule::enum(UserRole::class)]],
        )->validate();

        $user = User::query()->findOrFail($userId);
        $roleBaru = UserRole::from($role);

        if ($user->is(auth()->user())) {
            Notification::make()
                ->title('Tidak dapat mengubah role sendiri')
                ->body('Minta administrator lain untuk mengubah role akun Anda.')
                ->danger()
                ->send();

            return;
        }

        if ($user->role === UserRole::Admin && $roleBaru !== UserRole::Admin
            && User::query()->where('role', UserRole::Admin->value)->count() <= 1) {
            Notification::make()
                ->title('Administrator terakhir')
                ->body('Role administrator terakhir tidak boleh diturunkan.')
                ->danger()
                ->send();

            return;
        }

        if ($user->role === $roleBaru) {
            return;
        }

        $roleLama = $user->role;
        $user->update(['role' => $roleBaru->value]);

        LogAktivitas::catat(
            'user.role_diubah',
            "Role pengguna {$user->name} diubah dari {$roleLama->label()} menjadi {$roleBaru->label()}.",
            $user,
            [
                'role_lama' => $roleLama->value,
                'role_baru' => $roleBaru->value,
                'diubah_oleh' => auth()->id(),
            ]
        );

        Notification::make()
            ->title('Role diperbarui')
            ->body("{$user->name} sekarang {$roleBaru->label()}.")
            ->success()
            ->send();
    }
}
