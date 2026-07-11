<?php

namespace Shahkar\DataCenter;

use Illuminate\Support\ServiceProvider;
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Http\ShahkarHttpClient;
use Shahkar\DataCenter\Services\DataCenterApiService;

class ShahkarDataCenterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/shahkar-datacenter.php',
            'shahkar-datacenter'
        );

        $this->app->singleton(HttpClientInterface::class, function ($app) {
            return new ShahkarHttpClient(
                config('shahkar-datacenter')
            );
        });

        $this->app->singleton(DataCenterApiInterface::class, function ($app) {
            return new DataCenterApiService(
                $app->make(HttpClientInterface::class),
                config('shahkar-datacenter.operator_id', '013'),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/shahkar-datacenter.php' => config_path('shahkar-datacenter.php'),
            ], 'shahkar-datacenter-config');
        }
    }
}
