<?php

namespace Shahkar\DataCenter;

use Illuminate\Support\ServiceProvider;
use Shahkar\DataCenter\Contracts\CryptoServiceInterface;
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Crypto\NscraCryptoService;
use Shahkar\DataCenter\Http\ShahkarHttpClient;
use Shahkar\DataCenter\Services\DataCenterApiService;

class ShahkarDataCenterServiceProvider extends ServiceProvider
{
    /**
     * Path to the server public key bundled with the package. Used as the
     * default when SHAHKAR_SERVER_PUBLIC_KEY is not configured. Resolved
     * against the package location, so it stays valid after the config is
     * published into the host application.
     */
    public static function bundledServerPublicKeyPath(): string
    {
        return __DIR__ . '/../resources/keys/server_public_key.pem';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/shahkar-datacenter.php',
            'shahkar-datacenter'
        );

        $this->app->singleton(HttpClientInterface::class, function () {
            return new ShahkarHttpClient(config('shahkar-datacenter'));
        });

        $this->app->singleton(CryptoServiceInterface::class, function () {
            $config = config('shahkar-datacenter');

            // Fall back to the server public key bundled with the package when
            // none is configured (it is a fixed, non-secret value).
            $serverPublicKey = trim((string) ($config['server_public_key'] ?? '')) !== ''
                ? (string) $config['server_public_key']
                : self::bundledServerPublicKeyPath();

            return new NscraCryptoService(
                clientId:             (string) ($config['client_id'] ?? ''),
                clientPrivateKeyPem:  (string) ($config['client_private_key'] ?? ''),
                clientPublicKeyPem:   (string) ($config['client_public_key'] ?? ''),
                serverPublicKeyPem:   $serverPublicKey,
                clockSkew:            (int) ($config['clock_skew'] ?? 300),
            );
        });

        $this->app->singleton(DataCenterApiInterface::class, function ($app) {
            return new DataCenterApiService(
                $app->make(HttpClientInterface::class),
                $app->make(CryptoServiceInterface::class),
                config('shahkar-datacenter.provider_code', ''),
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
