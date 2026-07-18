<?php

namespace Shahkar\DataCenter;

use Illuminate\Support\ServiceProvider;
use Shahkar\DataCenter\Contracts\DataCenterApiV1Interface;
use Shahkar\DataCenter\Contracts\DataCenterApiV92Interface;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Contracts\IpRegistrationApiInterface;
use Shahkar\DataCenter\Http\ShahkarHttpClient;
use Shahkar\DataCenter\Services\DataCenterApiServiceV1;
use Shahkar\DataCenter\Services\DataCenterApiServiceV92;
use Shahkar\DataCenter\Services\IpRegistrationApiService;
use Shahkar\DataCenter\Support\ShahkarDataCenterManager;

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

        $this->app->singleton(DataCenterApiV1Interface::class, function ($app) {
            return new DataCenterApiServiceV1(
                $app->make(HttpClientInterface::class),
                config('shahkar-datacenter.operator_id', '013'),
            );
        });

        $this->app->singleton(DataCenterApiV92Interface::class, function ($app) {
            return new DataCenterApiServiceV92(
                $app->make(HttpClientInterface::class),
                config('shahkar-datacenter.operator_id', '013'),
                config('shahkar-datacenter.reseller_code', ''),
            );
        });

        $this->app->singleton(ShahkarDataCenterManager::class, function ($app) {
            return new ShahkarDataCenterManager(
                $app,
                config('shahkar-datacenter.default_version', '9.2'),
            );
        });

        // Standalone "IP Registration" (putIP) service — independent of the
        // Data Center versions above; shares only the HTTP client.
        $this->app->singleton(IpRegistrationApiInterface::class, function ($app) {
            return new IpRegistrationApiService(
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
