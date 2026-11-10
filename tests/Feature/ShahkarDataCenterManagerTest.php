<?php

namespace Shahkar\DataCenter\Tests\Feature;

use Illuminate\Container\Container;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\DataCenterApiV12Interface;
use Shahkar\DataCenter\Contracts\DataCenterApiV1Interface;
use Shahkar\DataCenter\Contracts\DataCenterApiV92Interface;
use Shahkar\DataCenter\Contracts\DataCenterApiV94Interface;
use Shahkar\DataCenter\Enums\ApiVersion;
use Shahkar\DataCenter\Support\ShahkarDataCenterManager;

class ShahkarDataCenterManagerTest extends TestCase
{
    private function makeContainer(): Container
    {
        $container = new Container();

        foreach ([
            DataCenterApiV1Interface::class,
            DataCenterApiV12Interface::class,
            DataCenterApiV92Interface::class,
            DataCenterApiV94Interface::class,
        ] as $contract) {
            $container->instance($contract, $this->createMock($contract));
        }

        return $container;
    }

    public function test_typed_accessors_return_concrete_contracts(): void
    {
        $manager = new ShahkarDataCenterManager($this->makeContainer(), '9.4');

        $this->assertInstanceOf(DataCenterApiV94Interface::class, $manager->v94());
        $this->assertInstanceOf(DataCenterApiV12Interface::class, $manager->v12());
        $this->assertInstanceOf(DataCenterApiV92Interface::class, $manager->v92());
        $this->assertInstanceOf(DataCenterApiV1Interface::class, $manager->v1());
    }

    public function test_version_resolves_the_matching_implementation(): void
    {
        $manager = new ShahkarDataCenterManager($this->makeContainer(), '9.4');

        $cases = [
            '9.4' => [ApiVersion::V9_4, DataCenterApiV94Interface::class],
            '1.2' => [ApiVersion::V1_2, DataCenterApiV12Interface::class],
            '9.2' => [ApiVersion::V9_2, DataCenterApiV92Interface::class],
            '1.0' => [ApiVersion::V1_0, DataCenterApiV1Interface::class],
        ];

        foreach ($cases as $key => [$enum, $contract]) {
            $this->assertInstanceOf($contract, $manager->version($key), "version('{$key}')");
            $this->assertInstanceOf($contract, $manager->version($enum), $enum->name);
        }
    }

    public function test_default_follows_the_configured_version(): void
    {
        $container = $this->makeContainer();

        $this->assertInstanceOf(DataCenterApiV94Interface::class, (new ShahkarDataCenterManager($container, '9.4'))->default());
        $this->assertInstanceOf(DataCenterApiV12Interface::class, (new ShahkarDataCenterManager($container, '1.2'))->default());
        $this->assertInstanceOf(DataCenterApiV92Interface::class, (new ShahkarDataCenterManager($container, '9.2'))->default());
        $this->assertInstanceOf(DataCenterApiV1Interface::class, (new ShahkarDataCenterManager($container, '1.0'))->default());
    }

    public function test_unknown_version_throws(): void
    {
        $manager = new ShahkarDataCenterManager($this->makeContainer(), '9.4');

        $this->expectException(InvalidArgumentException::class);
        $manager->version('999');
    }

    public function test_every_api_version_has_a_doc_reference(): void
    {
        foreach (ApiVersion::cases() as $version) {
            $this->assertNotSame('', $version->docReference());
        }
    }
}
