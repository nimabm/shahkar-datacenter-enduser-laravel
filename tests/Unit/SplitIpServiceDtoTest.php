<?php

namespace Shahkar\DataCenter\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\ServiceDataV12Interface;
use Shahkar\DataCenter\Contracts\ServiceDataV94Interface;
use Shahkar\DataCenter\DTOs\Service\CdnServiceV12DTO;
use Shahkar\DataCenter\DTOs\Service\CdnServiceV94DTO;
use Shahkar\DataCenter\DTOs\Service\CdnUpdateV12DTO;
use Shahkar\DataCenter\DTOs\Service\CdnUpdateV94DTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceV12DTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceV94DTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationUpdateV12DTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationUpdateV94DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceV12DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceV94DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateV12DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateV94DTO;
use Shahkar\DataCenter\DTOs\Service\VpsServiceV12DTO;
use Shahkar\DataCenter\DTOs\Service\VpsServiceV94DTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateV12DTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateV94DTO;
use Shahkar\DataCenter\Enums\DataCenterType;

/**
 * Service DTOs for the V9.4 / V1.2 documents, which split `ips` into
 * `publicIps` (required) and `privateIps` (optional).
 */
class SplitIpServiceDtoTest extends TestCase
{
    private const PUBLIC  = '185.168.12.10-185.168.12.10';
    private const PRIVATE = '192.168.1.1-192.168.1.10';

    public function test_shared_hosting_register_matches_doc_sample(): void
    {
        $args = [
            'dataCenterId' => '34689999',
            'publicIps'    => self::PUBLIC,
            'bandwidth'    => 256,
            'startDate'    => '13991211',
            'urlList'      => 'cra.ir',
            'privateIps'   => self::PRIVATE,
            'endDate'      => '13991212',
            'hasSSL'       => true,
            'hasIXP'       => true,
        ];

        $expected = [
            'type'           => 35,
            'dataCenterType' => 14,
            'dataCenterId'   => '34689999',
            'publicIps'      => self::PUBLIC,
            'privateIps'     => self::PRIVATE,
            'bandwidth'      => 256,
            'startDate'      => '13991211',
            'endDate'        => '13991212',
            'urlList'        => 'cra.ir',
            'hasSSL'         => 1,
            'hasIXP'         => 1,
        ];

        $this->assertSame($expected, (new SharedWebHostingServiceV94DTO(...$args))->toArray());
        $this->assertSame($expected, (new SharedWebHostingServiceV12DTO(...$args))->toArray());
    }

    public function test_register_dtos_never_send_legacy_ips_and_omit_null_private_ips(): void
    {
        $dtos = [
            new SharedWebHostingServiceV94DTO('1', self::PUBLIC, 256, '14030101', 'cra.ir'),
            new VpsServiceV94DTO('1', 'center', 'addr', self::PUBLIC, 256, '14030101'),
            new CdnServiceV94DTO('1', self::PUBLIC, 256, '14030101', 'cra.ir'),
            new DedicatedColocationServiceV94DTO('1', 'center', 'addr', '021', self::PUBLIC, 256, '14030101', '35.6', '51.3', 1, 1, 1),
            new SharedWebHostingServiceV12DTO('1', self::PUBLIC, 256, '14030101', 'cra.ir'),
            new VpsServiceV12DTO('1', 'center', 'addr', self::PUBLIC, 256, '14030101'),
            new CdnServiceV12DTO('1', self::PUBLIC, 256, '14030101', 'cra.ir'),
            new DedicatedColocationServiceV12DTO('1', 'center', 'addr', '021', self::PUBLIC, 256, '14030101', '35.6', '51.3', 1, 1, 1),
        ];

        foreach ($dtos as $dto) {
            $arr = $dto->toArray();
            $this->assertSame(self::PUBLIC, $arr['publicIps'], $dto::class);
            $this->assertArrayNotHasKey('privateIps', $arr, $dto::class);
            $this->assertArrayNotHasKey('ips', $arr, $dto::class);
        }
    }

    public function test_dtos_implement_only_their_own_version_marker(): void
    {
        $v94 = new VpsServiceV94DTO('1', 'center', 'addr', self::PUBLIC, 256, '14030101');
        $v12 = new VpsServiceV12DTO('1', 'center', 'addr', self::PUBLIC, 256, '14030101');

        $this->assertInstanceOf(ServiceDataV94Interface::class, $v94);
        $this->assertNotInstanceOf(ServiceDataV12Interface::class, $v94);
        $this->assertInstanceOf(ServiceDataV12Interface::class, $v12);
        $this->assertNotInstanceOf(ServiceDataV94Interface::class, $v12);

        $this->assertInstanceOf(ServiceDataV94Interface::class, new VpsUpdateV94DTO('1'));
        $this->assertInstanceOf(ServiceDataV12Interface::class, new VpsUpdateV12DTO());
    }

    public function test_dedicated_register_requires_province_and_keeps_rowindex(): void
    {
        $arr = (new DedicatedColocationServiceV12DTO(
            dataCenterId:      '34689999',
            centerName:        'Shaghayegh',
            dataCenterAddress: 'Tehran',
            province:          '021',
            publicIps:         self::PUBLIC,
            bandwidth:         256,
            startDate:         '13991211',
            lat:               '35.689198',
            lon:               '51.388973',
            rowIndex:          7,
            racIndex:          1,
            unitIndex:         1,
            dataCenterType:    DataCenterType::Colocation,
            privateIps:        self::PRIVATE,
            units:             4,
        ))->toArray();

        $this->assertSame(13, $arr['dataCenterType']);
        $this->assertSame('021', $arr['province']);
        $this->assertSame(7, $arr['rowIndex']);
        $this->assertSame(4, $arr['units']);
        $this->assertSame(self::PRIVATE, $arr['privateIps']);
        $this->assertArrayNotHasKey('rowtIndex', $arr);
    }

    public function test_dedicated_register_rejects_non_dedicated_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DedicatedColocationServiceV94DTO(
            '1', 'center', 'addr', '021', self::PUBLIC, 256, '14030101', '35.6', '51.3', 1, 1, 1,
            dataCenterType: DataCenterType::VPS,
        );
    }

    public function test_v94_update_dtos_carry_data_center_id(): void
    {
        $dtos = [
            new SharedWebHostingUpdateV94DTO('DC1', publicIps: self::PUBLIC, privateIps: self::PRIVATE),
            new VpsUpdateV94DTO('DC1', publicIps: self::PUBLIC, privateIps: self::PRIVATE),
            new DedicatedColocationUpdateV94DTO('DC1', publicIps: self::PUBLIC, privateIps: self::PRIVATE),
            new CdnUpdateV94DTO('DC1', publicIps: self::PUBLIC, privateIps: self::PRIVATE),
        ];

        foreach ($dtos as $dto) {
            $this->assertSame(
                ['dataCenterId' => 'DC1', 'publicIps' => self::PUBLIC, 'privateIps' => self::PRIVATE],
                $dto->toArray(),
                $dto::class,
            );
        }
    }

    public function test_v12_update_dtos_have_no_data_center_id(): void
    {
        $dtos = [
            new SharedWebHostingUpdateV12DTO(publicIps: self::PUBLIC, privateIps: self::PRIVATE),
            new VpsUpdateV12DTO(publicIps: self::PUBLIC, privateIps: self::PRIVATE),
            new DedicatedColocationUpdateV12DTO(publicIps: self::PUBLIC, privateIps: self::PRIVATE),
            new CdnUpdateV12DTO(publicIps: self::PUBLIC, privateIps: self::PRIVATE),
        ];

        foreach ($dtos as $dto) {
            $this->assertSame(
                ['publicIps' => self::PUBLIC, 'privateIps' => self::PRIVATE],
                $dto->toArray(),
                $dto::class,
            );
        }
    }

    public function test_dedicated_update_serializes_rack_position(): void
    {
        $arr = (new DedicatedColocationUpdateV12DTO(
            hasIXP:    true,
            rowIndex:  10,
            racIndex:  3,
            unitIndex: 12,
        ))->toArray();

        $this->assertSame(['hasIXP' => 1, 'rowIndex' => 10, 'racIndex' => 3, 'unitIndex' => 12], $arr);
    }
}
