<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppTier: string implements HasColor, HasLabel
{
    case Basic = 'basic';
    case Pro = 'pro';

    public function getLabel(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Pro => 'Pro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Basic => 'gray',
            self::Pro => 'primary',
        };
    }

    public function isPro(): bool
    {
        return $this === self::Pro;
    }

    public function isBasic(): bool
    {
        return $this === self::Basic;
    }

    public static function fromString(?string $value): self
    {
        return match (strtolower(trim((string) $value))) {
            'pro', 'enterprise', 'premium' => self::Pro,
            default => self::Basic,
        };
    }
}
