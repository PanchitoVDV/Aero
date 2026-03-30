<?php

namespace App\Providers;

use App\Services\VirtFusionService;
use App\Services\MollieService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VirtFusionService::class, function () {
            return new VirtFusionService();
        });

        $this->app->singleton(MollieService::class, function () {
            return new MollieService();
        });
    }

    public function boot(): void
    {
        //
    }
}
