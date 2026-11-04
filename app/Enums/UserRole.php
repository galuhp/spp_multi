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
            self::Admin => 'bg-purple-100 text-purple-800 border border-purple-200',
            self::Petugas => 'bg-blue-100 text-blue-800 border border-blue-200',
            self::Siswa => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
        };
    }
}
