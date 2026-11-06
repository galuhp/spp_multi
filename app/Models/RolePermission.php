<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['role', 'permission'])]
class RolePermission extends Model
{
    public const DASHBOARD = 'dashboard';

    public const TRANSAKSI_SPP = 'transaksi_spp';

    public const LAPORAN = 'laporan';

    public const PORTAL_SISWA = 'portal_siswa';

    public const KESISWAAN = 'kesiswaan';

    public const MASTER_DATA = 'master_data';

    public const PENGATURAN_AKSES = 'pengaturan_akses';

    /**
     * Permissions that can be toggled from the role management page.
     *
     * @var array<string, string>
     */
    public const AVAILABLE = [
        self::DASHBOARD => 'Dashboard',
        self::TRANSAKSI_SPP => 'Transaksi SPP',
        self::LAPORAN => 'Laporan',
        self::PORTAL_SISWA => 'Portal Siswa',
        self::KESISWAAN => 'Kesiswaan',
        self::MASTER_DATA => 'Master Data',
        self::PENGATURAN_AKSES => 'Pengaturan & Akses',
    ];

    /**
     * Determine whether a role may access a permission.
     */
    public static function allowed(UserRole $role, string $permission): bool
    {
        return static::query()
            ->where('role', $role->value)
            ->where('permission', $permission)
            ->exists();
    }

    /**
     * Permissions assigned to a role.
     *
     * @return array<int, string>
     */
    public static function forRole(UserRole $role): array
    {
        return static::query()
            ->where('role', $role->value)
            ->pluck('permission')
            ->all();
    }

    /**
     * Replace the permissions assigned to a role.
     *
     * @param  array<int, string>  $permissions
     */
    public static function syncRole(UserRole $role, array $permissions): void
    {
        $permissions = array_values(array_intersect(
            array_unique($permissions),
            array_keys(self::AVAILABLE),
        ));

        static::query()->where('role', $role->value)->delete();

        foreach ($permissions as $permission) {
            static::query()->create([
                'role' => $role->value,
                'permission' => $permission,
            ]);
        }
    }
}
