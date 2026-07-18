<?php

namespace Shahkar\DataCenter\Tests\Feature;

use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\DataCenterApiV1Interface;
use Shahkar\DataCenter\Contracts\DataCenterApiV92Interface;
use Shahkar\DataCenter\Enums\ApiVersion;
use Shahkar\DataCenter\Support\ShahkarDataCenterManager;

class ShahkarDataCenterManagerTest extends TestCase
{
    private function makeContainer(): Container
    {
        $container = new Container();
        $container->instance(
            DataCenterApiV1Interface::class,
            $this->createMock(DataCenterApiV1Interface::class),
        );
        $container->instance(
            DataCenterApiV92Interface::class,
            $this->createMock(DataCenterApiV92Interface::class),
        );

        return $container;
    }

    public function test_typed_accessors_return_concrete_contracts(): void
    {
        $manager = new ShahkarDataCenterManager($this->makeContainer(), '9.2');

        $this->assertInstanceOf(DataCenterApiV92Interface::class, $manager->v92());
        $this->assertInstanceOf(DataCenterApiV1Interface::class, $manager->v1());
    }

    public function test_version_resolves_the_matching_implementation(): void
    {
        $manager = new ShahkarDataCenterManager($this->makeContainer(), '9.2');

        $this->assertInstanceOf(DataCenterApiV92Interface::class, $manager->version('9.2'));
        $this->assertInstanceOf(DataCenterApiV92Interface::class, $manager->version(ApiVersion::V9_2));
        $this->assertInstanceOf(DataCenterApiV1Interface::class, $manager->version('1.0'));
        $this->assertInstanceOf(DataCenterApiV1Interface::class, $manager->version(ApiVersion::V1_0));
    }

    public function test_default_follows_the_configured_version(): void
    {
        $v92 = new ShahkarDataCenterManager($this->makeContainer(), '9.2');
        $v10 = new ShahkarDataCenterManager($this->makeContainer(), '1.0');

        $this->assertInstanceOf(DataCenterApiV92Interface::class, $v92->default());
        $this->assertInstanceOf(DataCenterApiV1Interface::class, $v10->default());
    }

    public function test_unknown_version_throws(): void
    {
        $manager = new ShahkarDataCenterManager($this->makeContainer(), '9.2');

        $this->expectException(InvalidArgumentException::class);
        $manager->version('999');
    }
}
