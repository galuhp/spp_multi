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
            self::Admin => 'bg-purple-100/90 text-purple-800 border border-purple-200/80 dark:bg-purple-500/15 dark:text-purple-300 dark:border-purple-400/25',
            self::Petugas => 'bg-sky-100/90 text-sky-800 border border-sky-200/80 dark:bg-sky-500/15 dark:text-sky-300 dark:border-sky-400/25',
            self::Siswa => 'bg-emerald-100/90 text-emerald-800 border border-emerald-200/80 dark:bg-emerald-500/15 dark:text-emerald-300 dark:border-emerald-400/25',
        };
    }
}
