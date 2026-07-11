<?php

namespace Shahkar\DataCenter\Tests\Feature;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Services\DataCenterApiService;

class DataCenterApiServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private DataCenterApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service    = new DataCenterApiService($this->httpClient, '013');
    }

    private function makeSuccessResponse(array $extra = []): ApiResponse
    {
        return new ApiResponse(
            success:    true,
            statusCode: 200,
            body:       array_merge(['message' => 'OK'], $extra),
            requestId:  'test-request-id',
        );
    }

    public function test_register_for_natural_person_sends_correct_payload(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/put',
                $this->callback(function (array $payload) {
                    return isset($payload['identificationType'])
                        && isset($payload['identificationNo'])
                        && isset($payload['address'])
                        && isset($payload['service'])
                        && $payload['service']['dataCenterType'] === 14; // SharedWebHosting
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $response = $this->service->registerForNaturalPerson(
            person:  new NaturalPersonDTO('0987654321'),
            address: new AddressDTO('021', 'Azadi Street', '10', '1234567890'),
            service: new SharedWebHostingServiceDTO('DC001', '1.2.3.4-1.2.3.4', 256, '14030101', 'cra.ir'),
        );

        $this->assertTrue($response->success);
    }

    public function test_register_for_legal_person_sends_correct_payload(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/put',
                $this->callback(function (array $payload) {
                    return isset($payload['identificationType'])
                        && $payload['identificationType'] === 5 // NationalId
                        && isset($payload['mobileNumber'])
                        && isset($payload['agentIdentificationNo']);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->registerForLegalPerson(
            person:  new LegalPersonDTO('33273340437', '09128964532', '0072314567'),
            address: new AddressDTO('021', 'Azadi Street', '10', '1234567890'),
            service: new SharedWebHostingServiceDTO('DC001', '1.2.3.4-1.2.3.4', 256, '14030101', 'cra.ir'),
        );
    }

    public function test_update_for_natural_person_sends_correct_payload(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/update',
                $this->callback(function (array $payload) {
                    return isset($payload['id'])
                        && isset($payload['serviceNumber'])
                        && isset($payload['otp'])
                        && isset($payload['serviceUpdate']);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->updateForNaturalPerson(
            serviceId:     'WZOzs3PX2rKT',
            serviceNumber: '34689658',
            otp:           12345,
            serviceUpdate: new SharedWebHostingUpdateDTO('DC001', ips: '1.2.3.5-1.2.3.5'),
        );
    }

    public function test_close_sends_minimal_payload(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/close',
                $this->callback(function (array $payload) {
                    return array_key_exists('requestId', $payload)
                        && array_key_exists('id', $payload)
                        && count($payload) === 2;
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->close('WZOzs3PX2rKT');
    }
}
