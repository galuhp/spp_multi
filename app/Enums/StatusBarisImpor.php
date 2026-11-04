<?php

namespace App\Enums;

enum StatusBarisImpor: string
{
    case Valid = 'valid';
    case Peringatan = 'peringatan';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::Peringatan => 'Peringatan',
            self::Error => 'Error',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Valid => 'badge badge-success badge-sm',
            self::Peringatan => 'badge badge-warning badge-sm',
            self::Error => 'badge badge-error badge-sm',
        };
    }
}
