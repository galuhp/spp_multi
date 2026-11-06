<?php

namespace App\Providers;

use BladeUI\Icons\Factory as IconFactory;
use Illuminate\Database\Eloquent\Model;
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
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
