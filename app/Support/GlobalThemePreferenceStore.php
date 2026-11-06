<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Saivra\MeUi\Support\ThemeOptions;
use Throwable;

final class GlobalThemePreferenceStore
{
    public const GLOBAL_KEY = 'admin_global_theme_preferences';

    protected const CACHE_KEY = 'meui.preferences.global.admin';

    /**
     * Read shared theme preferences for all users in the panel.
     *
     * @return array<string, string>
     */
    public static function read(): array
    {
        if (request()->attributes->has(self::CACHE_KEY)) {
            return request()->attributes->get(self::CACHE_KEY);
        }

        try {
            $values = Cache::rememberForever(self::CACHE_KEY, function (): array {
                if (! Schema::hasTable('meui_preferences')) {
                    return [];
                }

                $json = DB::table('meui_preferences')
                    ->where('key', self::GLOBAL_KEY)
                    ->value('preferences');

                $decoded = is_string($json) ? json_decode($json, true) : [];

                return is_array($decoded) ? ThemeOptions::sanitize($decoded) : [];
            });
        } catch (Throwable) {
            $values = [];
        }

        request()->attributes->set(self::CACHE_KEY, $values);

        return $values;
    }

    /**
     * Save shared theme preferences for all users in the panel.
     *
     * @param  array<string, string>  $values
     */
    public static function write(array $values): void
    {
        $sanitized = ThemeOptions::sanitize($values);

        if ($sanitized === []) {
            DB::table('meui_preferences')
                ->where('key', self::GLOBAL_KEY)
                ->delete();
        } else {
            DB::table('meui_preferences')->upsert([
                'key' => self::GLOBAL_KEY,
                'preferences' => json_encode($sanitized, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ], ['key'], ['preferences', 'updated_at']);
        }

        Cache::forget(self::CACHE_KEY);
        request()->attributes->set(self::CACHE_KEY, $sanitized);
    }

    /**
     * Resolve a single option value with fallback to the default.
     */
    public static function option(string $name, string $default): string
    {
        return self::read()[$name] ?? $default;
    }
}
