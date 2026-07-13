<?php

namespace Shahkar\DataCenter\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Support\ServiceTemplates;

class ServiceTemplatesTest extends TestCase
{
    public function test_put_real_shared_matches_expected_shape(): void
    {
        $t = ServiceTemplates::for('put', 'real', 'shared');

        $this->assertSame(0, $t['identificationType']);
        $this->assertArrayNotHasKey('agentIdentificationNo', $t);
        $this->assertSame(35, $t['service']['type']);
        $this->assertSame(14, $t['service']['dataCenterType']);
        $this->assertArrayHasKey('address', $t);
    }

    public function test_put_legal_includes_agent_fields(): void
    {
        $t = ServiceTemplates::for('put', 'legal', 'vps');

        $this->assertSame(5, $t['identificationType']);
        $this->assertSame('09128964532', $t['mobileNumber']);
        $this->assertSame(0, $t['agentIdentificationType']);
        $this->assertSame(11, $t['service']['dataCenterType']);
    }

    public function test_dedicated_and_colocation_use_correct_type_and_rowindex(): void
    {
        $dedicated  = ServiceTemplates::for('put', 'real', 'dedicated');
        $colocation = ServiceTemplates::for('put', 'real', 'colocation');

        $this->assertSame(12, $dedicated['service']['dataCenterType']);
        $this->assertSame(13, $colocation['service']['dataCenterType']);
        $this->assertSame(1, $dedicated['service']['rowIndex']);
        $this->assertArrayNotHasKey('rowtIndex', $dedicated['service']);
    }

    public function test_update_legal_includes_customer_update(): void
    {
        $t = ServiceTemplates::for('update', 'legal', 'cdn');

        $this->assertArrayHasKey('id', $t);
        $this->assertArrayHasKey('customerUpdate', $t);
        $this->assertSame(128, $t['serviceUpdate']['bandwidth']);
    }

    public function test_delete_template_has_only_id(): void
    {
        $t = ServiceTemplates::for('delete');

        $this->assertArrayHasKey('id', $t);
        $this->assertCount(1, $t);
    }

    public function test_invalid_service_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ServiceTemplates::for('put', 'real', 'nope');
    }
}
