<?php

namespace Shahkar\DataCenter\Tests\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Reseller\CustomerUpdateResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\LegalPersonResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\NaturalPersonResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\ResellerServiceDTO;
use Shahkar\DataCenter\DTOs\Reseller\ResellerServiceUpdateDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Services\ResellerApiService;

class ResellerApiServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private ResellerApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service    = new ResellerApiService($this->httpClient, '013', '12345');
    }

    private function makeSuccessResponse(): ApiResponse
    {
        return new ApiResponse(
            success:    true,
            statusCode: 200,
            body:       ['result' => 'OK.', 'response' => 200],
            requestId:  'test-request-id',
        );
    }

    public function test_register_natural_person_sends_type_30_and_service_block(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/put',
                $this->callback(function (array $payload) {
                    return $payload['resellerCode'] === '12345'      // top-level (registering)
                        && $payload['person'] === 1
                        && $payload['iranian'] === 1
                        && $payload['identificationType'] === 0
                        && $payload['service']['type'] === 30
                        && $payload['service']['resellerCode'] === '9896' // code being registered
                        && $payload['service']['ipStatic'] === 1
                        && !array_key_exists('otp', $payload);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->registerForNaturalPerson(
            person: new NaturalPersonResellerDTO(
                identificationNo: '0012345678',
                name:             'سیدمحمد',
                family:           'حسینی',
                mobile:           '09121234567',
                certificateNo:    '12345',
                birthDate:        '13671201',
            ),
            service: new ResellerServiceDTO(
                resellerCode: '9896',
                province:     '021',
                ipStatic:     true,
                rangeIps:     '100.100.100.20-100.100.100.30',
            ),
            address: new AddressDTO('021', 'خیابان مطهری', '18', '1345676543', townshipName: 'اسلامشهر'),
        );
    }

    public function test_register_legal_foreign_uses_type_6_and_agent_type(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/put',
                $this->callback(function (array $payload) {
                    return $payload['person'] === 0
                        && $payload['iranian'] === 0
                        && $payload['identificationType'] === 6
                        && $payload['agentIdentificationType'] === 0 // Iranian agent
                        && $payload['nationality'] === 'USA';
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->registerForLegalPerson(
            person: new LegalPersonResellerDTO(
                identificationNo:      '357812321',
                mobile:                '09121234567',
                companyName:           'Apple',
                agentIdentificationNo: '0012345678',
                iranian:               false,
                nationality:           'USA',
                companyType:           4,
            ),
            service: new ResellerServiceDTO('9896', '021'),
        );
    }

    public function test_update_hits_update_endpoint_with_blocks(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/update',
                $this->callback(function (array $payload) {
                    return $payload['id'] === 'svc-id'
                        && $payload['serviceNumber'] === '9896'
                        && $payload['customerUpdate']['name'] === 'ساناز'
                        && $payload['serviceUpdate']['ipStatic'] === 1
                        && !array_key_exists('transfer', $payload);
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->update(
            serviceId:      'svc-id',
            serviceNumber:  '9896',
            serviceUpdate:  new ResellerServiceUpdateDTO(ipStatic: true, rangeIps: '100.100.100.20-100.100.100.30'),
            customerUpdate: new CustomerUpdateResellerDTO(name: 'ساناز', email: 'sample@smp.com'),
        );
    }

    public function test_transfer_sends_transfer_flag_and_request_block(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/update',
                $this->callback(function (array $payload) {
                    return $payload['transfer'] === 1
                        && $payload['serviceNumber'] === '9896'
                        && $payload['transferRequest']['person'] === 1
                        && $payload['transferRequest']['identificationNo'] === '0031245698'
                        && $payload['transferRequest']['address']['provinceCode'] === '021';
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->transferToNaturalPerson(
            serviceId:     'svc-id',
            serviceNumber: '9896',
            person: new NaturalPersonResellerDTO(
                identificationNo: '0031245698',
                name:             'زهرا',
                family:           'علوی',
                mobile:           '09127654321',
                gender:           2,
            ),
            address: new AddressDTO('021', 'خیابان یکم', '6', '1445876543', townshipName: 'بهارستان'),
        );
    }

    public function test_close_sends_close_flag_to_update_endpoint(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/update',
                $this->callback(fn (array $payload) => $payload['close'] === 1 && $payload['serviceNumber'] === '9896')
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->close('svc-id', '9896');
    }

    public function test_delete_hits_delete_endpoint(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/delete',
                $this->callback(fn (array $payload) => $payload['serviceNumber'] === '9896' && !array_key_exists('close', $payload))
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->delete('svc-id', '9896');
    }

    public function test_service_dto_requires_range_ips_when_static(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ResellerServiceDTO(resellerCode: '9896', province: '021', ipStatic: true);
    }
}
