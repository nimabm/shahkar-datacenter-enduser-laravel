<?php

namespace Shahkar\DataCenter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\DTOs\Service\VpsServiceDTO;
use Shahkar\DataCenter\DTOs\Service\CdnServiceDTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceDTO;
use Shahkar\DataCenter\Enums\DataCenterType;
use Shahkar\DataCenter\Enums\IdentificationType;
use Shahkar\DataCenter\Enums\ServiceType;

class DtoSerializationTest extends TestCase
{
    public function test_natural_person_dto_serializes_without_otp(): void
    {
        $dto = new NaturalPersonDTO(identificationNo: '0987654321');
        $arr = $dto->toArray();

        $this->assertSame(IdentificationType::NationalCode->value, $arr['identificationType']);
        $this->assertSame('0987654321', $arr['identificationNo']);
        $this->assertArrayNotHasKey('otp', $arr);
        $this->assertArrayNotHasKey('nationality', $arr);
    }

    public function test_natural_person_dto_serializes_with_otp(): void
    {
        $dto = new NaturalPersonDTO(identificationNo: '0987654321', otp: 12345);
        $arr = $dto->toArray();

        $this->assertSame(12345, $arr['otp']);
    }

    public function test_legal_person_dto_includes_all_required_fields(): void
    {
        $dto = new LegalPersonDTO(
            identificationNo:      '33273340437',
            mobileNumber:          '09128964532',
            agentIdentificationNo: '0072314567',
        );
        $arr = $dto->toArray();

        $this->assertSame(IdentificationType::NationalId->value, $arr['identificationType']);
        $this->assertSame('33273340437', $arr['identificationNo']);
        $this->assertSame('09128964532', $arr['mobileNumber']);
        $this->assertSame(IdentificationType::NationalCode->value, $arr['agentIdentificationType']);
        $this->assertSame('0072314567', $arr['agentIdentificationNo']);
        $this->assertArrayNotHasKey('otp', $arr);
        $this->assertArrayNotHasKey('agentOtp', $arr);
    }

    public function test_shared_hosting_dto_has_correct_type_values(): void
    {
        $dto = new SharedWebHostingServiceDTO(
            dataCenterId: '34689999',
            ips:          '185.168.12.10-185.168.12.10',
            bandwidth:    256,
            startDate:    '13991211',
            urlList:      'cra.ir',
            hasSSL:       true,
            hasIXP:       true,
        );
        $arr = $dto->toArray();

        $this->assertSame(ServiceType::DataCenter->value, $arr['type']);
        $this->assertSame(DataCenterType::SharedWebHosting->value, $arr['dataCenterType']);
        $this->assertSame(1, $arr['hasSSL']);
        $this->assertSame(1, $arr['hasIXP']);
    }

    public function test_address_dto_omits_null_optional_fields(): void
    {
        $dto = new AddressDTO(
            provinceCode: '021',
            address:      'خیابان آزادی',
            houseNumber:  '10',
            postalCode:   '1234567890',
        );
        $arr = $dto->toArray();

        $this->assertArrayNotHasKey('townshipName', $arr);
        $this->assertArrayNotHasKey('street2', $arr);
        $this->assertArrayNotHasKey('tel', $arr);
        $this->assertSame('021', $arr['provinceCode']);
    }

    public function test_dedicated_server_dto_throws_on_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DedicatedColocationServiceDTO(
            dataCenterId:      '34689999',
            centerName:        'test',
            dataCenterAddress: 'address',
            ips:               '1.2.3.4-1.2.3.4',
            bandwidth:         256,
            startDate:         '14030101',
            lat:               '35.68',
            lon:               '51.38',
            rowIndex:          1,
            racIndex:          1,
            unitIndex:         1,
            dataCenterType:    DataCenterType::SharedWebHosting, // wrong type
        );
    }
}
