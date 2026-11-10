<?php

namespace Shahkar\DataCenter\Tests\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV1DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV1DTO;
use Shahkar\DataCenter\DTOs\Service\CdnUpdateV12DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceV12DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateV12DTO;
use Shahkar\DataCenter\DTOs\Service\VpsServiceV12DTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateV12DTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Services\DataCenterApiServiceV12;
use TypeError;

class DataCenterApiServiceV12Test extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private DataCenterApiServiceV12 $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service    = new DataCenterApiServiceV12($this->httpClient, '013');
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

    private function sharedHosting(): SharedWebHostingServiceV12DTO
    {
        return new SharedWebHostingServiceV12DTO(
            dataCenterId: '34689999',
            publicIps:    '185.168.12.10-185.168.12.10',
            bandwidth:    256,
            startDate:    '13991211',
            urlList:      'cra.ir',
            privateIps:   '192.168.1.1-192.168.1.10',
        );
    }

    public function test_register_natural_person_step_one_has_no_otp_and_split_ips(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/put',
                $this->callback(function (array $payload) {
                    $service = $payload['service'];

                    return $payload['requestId'] === 'req-1'
                        && $payload['identificationType'] === 0
                        && $payload['identificationNo'] === '0987654321'
                        && !array_key_exists('otp', $payload)
                        && !array_key_exists('resellerCode', $payload)
                        && $service['dataCenterType'] === 14
                        && $service['publicIps'] === '185.168.12.10-185.168.12.10'
                        && $service['privateIps'] === '192.168.1.1-192.168.1.10'
                        && !array_key_exists('ips', $service);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->registerForNaturalPerson(
            person:    new NaturalPersonV1DTO('0987654321'),
            address:   $this->address(),
            service:   $this->sharedHosting(),
            requestId: 'req-1',
        );
    }

    public function test_register_legal_person_step_two_sends_both_otps(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/put',
                $this->callback(function (array $payload) {
                    return $payload['identificationType'] === 5
                        && $payload['mobileNumber'] === '09128964532'
                        && $payload['otp'] === 1234
                        && $payload['agentOtp'] === 56781
                        && $payload['service']['dataCenterType'] === 11
                        && $payload['service']['publicIps'] === '185.168.12.10-185.168.12.10'
                        && !array_key_exists('privateIps', $payload['service']);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->registerForLegalPerson(
            person: new LegalPersonV1DTO(
                identificationNo:      '33273340437',
                mobileNumber:          '09128964532',
                agentIdentificationNo: '0072314567',
                otp:                   1234,
                agentOtp:              56781,
            ),
            address: $this->address(),
            service: new VpsServiceV12DTO(
                dataCenterId:      '34689999',
                centerName:        'Shaghayegh',
                dataCenterAddress: 'Tehran',
                publicIps:         '185.168.12.10-185.168.12.10',
                bandwidth:         256,
                startDate:         '13991211',
            ),
        );
    }

    public function test_update_natural_person_identifies_service_by_id_only(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/update',
                $this->callback(function (array $payload) {
                    return $payload['id'] === 'svc-id'
                        && $payload['otp'] === 12341
                        && !array_key_exists('serviceNumber', $payload)
                        && !array_key_exists('customerUpdate', $payload)
                        && !array_key_exists('agentOtp', $payload)
                        && $payload['addressUpdate']['townshipName'] === 'Firuzkuh'
                        && $payload['serviceUpdate'] === [
                            'publicIps'  => '185.168.12.10-185.168.12.10',
                            'privateIps' => '192.168.1.1-192.168.1.10',
                            'hasIXP'     => 1,
                        ];
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->updateForNaturalPerson(
            serviceId:     'svc-id',
            serviceUpdate: new SharedWebHostingUpdateV12DTO(
                publicIps:  '185.168.12.10-185.168.12.10',
                privateIps: '192.168.1.1-192.168.1.10',
                hasIXP:     true,
            ),
            addressUpdate: new AddressUpdateDTO(townshipName: 'Firuzkuh'),
            otp:           12341,
        );
    }

    public function test_update_step_one_is_sent_without_otp(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/update',
                $this->callback(function (array $payload) {
                    return $payload['id'] === 'svc-id'
                        && !array_key_exists('otp', $payload)
                        && !array_key_exists('agentOtp', $payload)
                        && $payload['serviceUpdate'] === ['bandwidth' => 512];
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->updateForLegalPerson(
            serviceId:     'svc-id',
            serviceUpdate: new VpsUpdateV12DTO(bandwidth: 512),
        );
    }

    public function test_update_legal_person_sends_both_otps(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/update',
                $this->callback(function (array $payload) {
                    return $payload['otp'] === 1234
                        && $payload['agentOtp'] === 56781
                        && $payload['serviceUpdate']['hasSSL'] === 1
                        && $payload['serviceUpdate']['bandwidth'] === 128
                        && !array_key_exists('serviceNumber', $payload)
                        && !array_key_exists('customerUpdate', $payload);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->updateForLegalPerson(
            serviceId:     'svc-id',
            serviceUpdate: new CdnUpdateV12DTO(hasSSL: true, bandwidth: 128),
            otp:           1234,
            agentOtp:      56781,
        );
    }

    public function test_address_only_update_omits_empty_service_update(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/datacenter/update',
                $this->callback(function (array $payload) {
                    return !array_key_exists('serviceUpdate', $payload)
                        && $payload['addressUpdate']['postalCode'] === '7654316543';
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->updateForNaturalPerson(
            serviceId:     'svc-id',
            serviceUpdate: new SharedWebHostingUpdateV12DTO(),
            addressUpdate: new AddressUpdateDTO(postalCode: '7654316543'),
            otp:           12341,
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
                        && $payload['id'] === 'svc-id'
                        && count($payload) === 2;
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->close('svc-id');
    }

    public function test_legacy_ips_dto_is_rejected(): void
    {
        $this->httpClient->expects($this->never())->method('post');
        $this->expectException(TypeError::class);

        $this->service->registerForNaturalPerson(
            new NaturalPersonV1DTO('0987654321'),
            $this->address(),
            // @phpstan-ignore-next-line — intentionally the wrong (pre-1.2) DTO
            new SharedWebHostingServiceDTO('34689999', '185.168.12.10-185.168.12.10', 256, '13991211', 'cra.ir'),
        );
    }
}
