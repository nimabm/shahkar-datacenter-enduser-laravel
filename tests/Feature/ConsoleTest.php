<?php

namespace Shahkar\DataCenter\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Shahkar\DataCenter\ShahkarDataCenterServiceProvider;
use Shahkar\DataCenter\Tests\Unit\NscraCryptoServiceTest;

class ConsoleTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ShahkarDataCenterServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        [$priv, $pub] = NscraCryptoServiceTest::generateKeyPair();

        $app['config']->set('shahkar-datacenter.client_id', 'client-1');
        $app['config']->set('shahkar-datacenter.provider_code', 'PRV');
        $app['config']->set('shahkar-datacenter.client_private_key', $priv);
        $app['config']->set('shahkar-datacenter.client_public_key', $pub);
        // server_public_key intentionally left empty -> bundled default is used.
    }

    public function test_keygen_prints_key_pair(): void
    {
        $this->artisan('shahkar:keygen')
            ->expectsOutputToContain('BEGIN')
            ->assertExitCode(0);
    }

    public function test_datacenter_console_boots_and_exits(): void
    {
        $options = [
            'Generate client key pair',
            'Register public key (2-step OTP)',
            'Register a service (put)',
            'Update a service',
            'Delete a service',
            'Check status by tracking id',
            'Exit',
        ];

        $this->artisan('shahkar:datacenter')
            ->expectsChoice('Select an action', 'Exit', $options)
            ->assertExitCode(0);
    }

    public function test_bundled_server_key_lets_container_resolve_api(): void
    {
        // Resolving the API requires the crypto service, which uses the bundled
        // server public key by default. This must not throw.
        $api = $this->app->make(\Shahkar\DataCenter\Contracts\DataCenterApiInterface::class);

        $this->assertInstanceOf(\Shahkar\DataCenter\Services\DataCenterApiService::class, $api);
    }
}
