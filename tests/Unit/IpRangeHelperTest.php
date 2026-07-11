<?php

namespace Shahkar\DataCenter\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Support\IpRangeHelper;

class IpRangeHelperTest extends TestCase
{
    public function test_formats_single_ip_range(): void
    {
        $result = IpRangeHelper::format(['185.168.1.1-185.168.1.250']);
        $this->assertSame('185.168.1.1-185.168.1.250', $result);
    }

    public function test_formats_multiple_ranges_with_comma(): void
    {
        $result = IpRangeHelper::format([
            '185.168.1.1-185.168.1.250',
            '185.168.2.1-185.168.2.100',
        ]);
        $this->assertSame('185.168.1.1-185.168.1.250,185.168.2.1-185.168.2.100', $result);
    }

    public function test_validates_valid_ranges_without_exception(): void
    {
        $this->expectNotToPerformAssertions();
        IpRangeHelper::validate(['185.168.1.1-185.168.1.250', '185.168.2.1-185.168.2.10']);
    }

    public function test_throws_on_invalid_ip(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IpRangeHelper::validate(['999.999.999.999-999.999.999.999']);
    }

    public function test_throws_on_overlapping_ranges(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IpRangeHelper::validate([
            '185.168.1.1-185.168.1.250',
            '185.168.1.200-185.168.1.255', // overlaps
        ]);
    }

    public function test_throws_when_start_greater_than_end(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IpRangeHelper::validate(['185.168.1.100-185.168.1.1']);
    }
}
