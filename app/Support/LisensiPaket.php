<?php

namespace App\Support;

use App\Enums\AppTier;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LisensiPaket
{
    /**
     * Current active application tier (Basic or Pro).
     */
    public static function tier(): AppTier
    {
        return AppTier::fromString((string) config('spp.tier', 'basic'));
    }

    /**
     * Check whether the application is running on the Pro tier.
     */
    public static function isPro(): bool
    {
        return self::tier()->isPro();
    }

    /**
     * Check whether the application is running on the Basic tier.
     */
    public static function isBasic(): bool
    {
        return self::tier()->isBasic();
    }

    /**
     * Human-readable tier label ("Basic" or "Pro").
     */
    public static function label(): string
    {
        return self::tier()->getLabel();
    }

    /**
     * Check if a specific feature key is enabled in the current tier.
     *
     * By default:
     * - If the feature is explicitly listed in `config('spp.features')`, its required tier is checked.
     * - Any feature not constrained to `pro` is allowed on both `basic` and `pro`.
     */
    public static function hasFeature(string $feature): bool
    {
        if (self::isPro()) {
            return true;
        }

        $features = (array) config('spp.features', []);
        $requiredTier = $features[$feature] ?? 'pro';

        return AppTier::fromString((string) $requiredTier)->isBasic();
    }

    /**
     * Abort with 403 if the current installation is not on the Pro tier (or lacks the given feature).
     */
    public static function abortUnlessPro(?string $feature = null, ?string $message = null): void
    {
        $allowed = $feature !== null ? self::hasFeature($feature) : self::isPro();

        if (! $allowed) {
            throw new HttpException(
                403,
                $message ?: 'Fitur ini hanya tersedia pada paket E-SPP Pro.',
            );
        }
    }
}
