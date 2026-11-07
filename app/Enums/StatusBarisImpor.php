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
            self::Valid => 'inline-flex items-center rounded-md border border-emerald-200/80 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700',
            self::Peringatan => 'inline-flex items-center rounded-md border border-amber-200/80 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700',
            self::Error => 'inline-flex items-center rounded-md border border-rose-200/80 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700',
        };
    }
}
