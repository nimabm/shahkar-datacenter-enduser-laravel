<?php

namespace Shahkar\DataCenter\Tests\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\CustomerUpdateV92DTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Services\DataCenterApiServiceV92;

class DataCenterApiServiceV92Test extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private DataCenterApiServiceV92 $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service    = new DataCenterApiServiceV92($this->httpClient, '013', '526');
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

    public function test_register_natural_person_hits_put_endpoint_without_otp(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/put',
                $this->callback(function (array $payload) {
                    return $payload['resellerCode'] === '526'
                        && $payload['identificationType'] === 0
                        && $payload['iranian'] === 1
                        && $payload['person'] === 1
                        && $payload['name'] === 'علی'
                        && $payload['mobile'] === '09127613814'
                        && !array_key_exists('otp', $payload)
                        && isset($payload['address'], $payload['service'])
                        && $payload['service']['dataCenterType'] === 14;
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
            address: new AddressDTO('021', 'Motahari St.', '18', '1345676543'),
            service: new SharedWebHostingServiceDTO('34689999', '185.168.12.10-185.168.12.10', 256, '13991211', 'cra.ir'),
        );
    }

    public function test_register_foreign_legal_person_uses_expected_identity_codes(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/put',
                $this->callback(function (array $payload) {
                    return $payload['identificationType'] === 6
                        && $payload['agentIdentificationType'] === 1
                        && $payload['iranian'] === 0
                        && $payload['person'] === 0
                        && $payload['nationality'] === 'USA';
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->registerForLegalPerson(
            person: new LegalPersonV92DTO(
                identificationNo:      '11165432503',
                mobile:                '09124443289',
                companyName:           'Green Electronics',
                agentIdentificationNo: '962780723',
                iranian:               false,
                nationality:           'USA',
            ),
            address: new AddressDTO('021', 'Motahari St.', '18', '1345676543'),
            service: new SharedWebHostingServiceDTO('34689999', '185.168.12.10-185.168.12.10', 256, '13991211', 'cra.ir'),
        );
    }

    public function test_update_hits_update_endpoint_with_customer_update(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/update',
                $this->callback(function (array $payload) {
                    return $payload['id'] === 'svc-id'
                        && $payload['serviceNumber'] === '34689658'
                        && !array_key_exists('otp', $payload)
                        && $payload['customerUpdate']['email'] === 'x@y.com'
                        && $payload['serviceUpdate']['ips'] === '185.168.12.11-185.168.12.11';
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->update(
            serviceId:      'svc-id',
            serviceNumber:  '34689658',
            serviceUpdate:  new SharedWebHostingUpdateDTO('DC001', ips: '185.168.12.11-185.168.12.11'),
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
                        && !array_key_exists('close', $payload);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->delete('svc-id', '85231');
    }
}
