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
            self::Valid => 'inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700',
            self::Peringatan => 'inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700',
            self::Error => 'inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700',
        };
    }
}
