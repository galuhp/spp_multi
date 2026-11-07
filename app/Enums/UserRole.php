<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';
    case Siswa = 'siswa';

    /**
     * Get human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Petugas => 'Petugas',
            self::Siswa => 'Siswa',
        };
    }

    /**
     * Get CSS badge color classes.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Admin => 'bg-purple-50 text-purple-700 border border-purple-200/80',
            self::Petugas => 'bg-blue-50 text-blue-700 border border-blue-200/80',
            self::Siswa => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80',
        };
    }
}
