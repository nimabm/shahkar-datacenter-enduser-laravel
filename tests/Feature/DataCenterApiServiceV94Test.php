<?php

namespace Shahkar\DataCenter\Tests\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\CustomerUpdateV92DTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceV94DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceV94DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateV94DTO;
use Shahkar\DataCenter\Enums\DataCenterType;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Services\DataCenterApiServiceV94;
use TypeError;

class DataCenterApiServiceV94Test extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private DataCenterApiServiceV94 $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service    = new DataCenterApiServiceV94($this->httpClient, '013', '526');
    }

    private function makeSuccessResponse(): ApiResponse
    {
        return new ApiResponse(
            success:    true,
            statusCode: 200,
            body:       ['message' => 'OK'],
            requestId:  'test-request-id',
        );
    }

    private function address(): AddressDTO
    {
        return new AddressDTO('021', 'Motahari St.', '18', '1345676543');
    }

    public function test_register_natural_person_sends_public_and_private_ips(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/put',
                $this->callback(function (array $payload) {
                    $service = $payload['service'];

                    return $payload['resellerCode'] === '526'
                        && $payload['identificationType'] === 0
                        && $payload['person'] === 1
                        && !array_key_exists('otp', $payload)
                        && $service['type'] === 35
                        && $service['dataCenterType'] === 14
                        && $service['publicIps'] === '185.168.12.10-185.168.12.10'
                        && $service['privateIps'] === '192.168.1.1-192.168.1.10'
                        && !array_key_exists('ips', $service);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->registerForNaturalPerson(
            person: new NaturalPersonV92DTO(
                identificationNo: '0987654321',
                name:             'علی',
                family:           'صارمی',
                mobile:           '09127613814',
            ),
            address: $this->address(),
            service: new SharedWebHostingServiceV94DTO(
                dataCenterId: '34689999',
                publicIps:    '185.168.12.10-185.168.12.10',
                bandwidth:    256,
                startDate:    '13991211',
                urlList:      'cra.ir',
                privateIps:   '192.168.1.1-192.168.1.10',
            ),
        );
    }

    public function test_register_legal_person_omits_private_ips_when_not_given(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/put',
                $this->callback(function (array $payload) {
                    $service = $payload['service'];

                    return $payload['identificationType'] === 5
                        && $payload['person'] === 0
                        && $service['dataCenterType'] === 13
                        && $service['province'] === '021'
                        && $service['publicIps'] === '185.168.12.10-185.168.12.10'
                        && !array_key_exists('privateIps', $service)
                        && !array_key_exists('ips', $service);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->registerForLegalPerson(
            person: new LegalPersonV92DTO(
                identificationNo:      '33273340437',
                mobile:                '09127601880',
                companyName:           'Dadeh Pardazan',
                agentIdentificationNo: '0072314567',
            ),
            address: $this->address(),
            service: new DedicatedColocationServiceV94DTO(
                dataCenterId:      '34689999',
                centerName:        'Shaghayegh',
                dataCenterAddress: 'Tehran',
                province:          '021',
                publicIps:         '185.168.12.10-185.168.12.10',
                bandwidth:         256,
                startDate:         '13991211',
                lat:               '35.689198',
                lon:               '51.388973',
                rowIndex:          1,
                racIndex:          1,
                unitIndex:         1,
                dataCenterType:    DataCenterType::Colocation,
            ),
        );
    }

    public function test_update_sends_service_number_and_split_ips(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/update',
                $this->callback(function (array $payload) {
                    return $payload['resellerCode'] === '526'
                        && $payload['id'] === 'svc-id'
                        && $payload['serviceNumber'] === '34689658'
                        && !array_key_exists('otp', $payload)
                        && $payload['customerUpdate']['email'] === 'x@y.com'
                        && $payload['serviceUpdate'] === [
                            'dataCenterId' => '34689658',
                            'publicIps'    => '185.168.12.10-185.168.12.10',
                            'privateIps'   => '192.168.1.1-192.168.1.10',
                            'hasIXP'       => 1,
                        ];
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->update(
            serviceId:      'svc-id',
            serviceNumber:  '34689658',
            serviceUpdate:  new SharedWebHostingUpdateV94DTO(
                dataCenterId: '34689658',
                publicIps:    '185.168.12.10-185.168.12.10',
                privateIps:   '192.168.1.1-192.168.1.10',
                hasIXP:       true,
            ),
            customerUpdate: new CustomerUpdateV92DTO(email: 'x@y.com'),
        );
    }

    public function test_close_sends_close_flag_to_update_endpoint(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/update',
                $this->callback(function (array $payload) {
                    return $payload['close'] === 1
                        && $payload['resellerCode'] === '526'
                        && $payload['id'] === 'svc-id'
                        && $payload['serviceNumber'] === '54123';
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->close('svc-id', '54123');
    }

    public function test_delete_hits_delete_endpoint(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/delete',
                $this->callback(function (array $payload) {
                    return $payload['id'] === 'svc-id'
                        && $payload['serviceNumber'] === '85231'
                        && $payload['resellerCode'] === '526'
                        && !array_key_exists('close', $payload);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->delete('svc-id', '85231');
    }

    public function test_legacy_ips_dto_is_rejected(): void
    {
        $this->httpClient->expects($this->never())->method('post');
        $this->expectException(TypeError::class);

        $this->service->registerForNaturalPerson(
            new NaturalPersonV92DTO('0987654321', 'Ali', 'Saremi', '09127613814'),
            $this->address(),
            // @phpstan-ignore-next-line — intentionally the wrong (pre-9.4) DTO
            new SharedWebHostingServiceDTO('34689999', '185.168.12.10-185.168.12.10', 256, '13991211', 'cra.ir'),
        );
    }
}
