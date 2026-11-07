<?php

namespace App\Filament\Concerns;

use App\Support\LisensiPaket;

/**
 * Trait for Filament Pages, Resources, or Widgets that are exclusive to the Pro segment.
 *
 * Attach `use RequiresProTier;` to any future Pro resource/page to automatically
 * gate access and optionally display a "PRO" badge in the sidebar navigation.
 */
trait RequiresProTier
{
    public static function requiredProFeature(): ?string
    {
        return property_exists(static::class, 'proFeatureKey')
            ? static::$proFeatureKey
            : null;
    }

    public static function isProFeatureAllowed(): bool
    {
        $feature = static::requiredProFeature();

        return $feature !== null
            ? LisensiPaket::hasFeature($feature)
            : LisensiPaket::isPro();
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! static::isProFeatureAllowed() && ! config('spp.show_locked_pro_navigation', false)) {
            return false;
        }

        return parent::shouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        if (! static::isProFeatureAllowed()) {
            return false;
        }

        return parent::canAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::isProFeatureAllowed()) {
            return 'PRO';
        }

        return parent::getNavigationBadge();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        if (! static::isProFeatureAllowed()) {
            return 'primary';
        }

        return parent::getNavigationBadgeColor();
    }
}
