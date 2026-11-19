<?php

namespace App\Providers;

use App\Support\LisensiPaket;
use BladeUI\Icons\Factory as IconFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->callAfterResolving(IconFactory::class, function (IconFactory $factory): void {
            $factory->add('fontawesome-brands', [
                'path' => resource_path('svg/fab'),
                'prefix' => 'fab',
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->isProduction() || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Model::preventLazyLoading(! $this->app->isProduction());

        Blade::if('pro', fn (?string $feature = null): bool => $feature !== null
            ? LisensiPaket::hasFeature($feature)
            : LisensiPaket::isPro());

        Blade::if('basic', fn (): bool => LisensiPaket::isBasic());

        Blade::if('feature', fn (string $feature): bool => LisensiPaket::hasFeature($feature));
    }
}
