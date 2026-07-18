<?php

namespace Shahkar\DataCenter\Tests\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Services\IpRegistrationApiService;

class IpRegistrationApiServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private IpRegistrationApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service    = new IpRegistrationApiService($this->httpClient, '013');
    }

    private function makeSuccessResponse(array $extra = []): ApiResponse
    {
        return new ApiResponse(
            success:    true,
            statusCode: 200,
            body:       array_merge(['result' => 'OK.', 'response' => 200], $extra),
            requestId:  'test-request-id',
        );
    }

    public function test_put_sends_all_three_lists_to_putip_endpoint(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/putIP',
                $this->callback(function (array $payload) {
                    return array_key_exists('requestId', $payload)
                        && $payload['endUsersIPs'] === '66.171.248.170-66.171.248.215'
                        && $payload['dataCentersIPs'] === '71.151.48.16-71.151.48.30'
                        && $payload['otherOperatorsIPs'] === '192.168.14.21-192.168.14.30';
                })
            )
            ->willReturn($this->makeSuccessResponse(['id' => 'TRACK123']));

        $response = $this->service->put(
            endUsersIPs:       '66.171.248.170-66.171.248.215',
            dataCentersIPs:    '71.151.48.16-71.151.48.30',
            otherOperatorsIPs: '192.168.14.21-192.168.14.30',
        );

        $this->assertTrue($response->success);
        $this->assertSame('TRACK123', $response->get('id'));
    }

    public function test_put_formats_array_inputs_into_comma_joined_ranges(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/putIP',
                $this->callback(function (array $payload) {
                    return $payload['endUsersIPs'] === '66.171.248.170-66.171.248.215,64.20.21.2-64.20.21.2'
                        && $payload['dataCentersIPs'] === '71.151.48.16-71.151.48.30'
                        && $payload['otherOperatorsIPs'] === '192.168.14.21-192.168.14.30';
                })
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->put(
            endUsersIPs:       ['66.171.248.170-66.171.248.215', '64.20.21.2-64.20.21.2'],
            dataCentersIPs:    ['71.151.48.16-71.151.48.30'],
            otherOperatorsIPs: ['192.168.14.21-192.168.14.30'],
        );
    }

    public function test_put_uses_provided_request_id(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/putIP',
                $this->callback(fn (array $payload) => $payload['requestId'] === 'my-req-id')
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->put('1.1.1.1-1.1.1.1', '2.2.2.2-2.2.2.2', '3.3.3.3-3.3.3.3', 'my-req-id');
    }

    public function test_truncate_sends_only_request_id_to_truncateip_endpoint(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/truncateIP',
                $this->callback(fn (array $payload) => array_keys($payload) === ['requestId'])
            )
            ->willReturn($this->makeSuccessResponse());

        $this->service->truncate();
    }

    public function test_fetch_hits_fetchip_endpoint_and_exposes_lists(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/fetchIP',
                $this->callback(fn (array $payload) => array_keys($payload) === ['requestId'])
            )
            ->willReturn($this->makeSuccessResponse([
                'endUsersIPs'       => '150.0.1.0-150.0.1.120',
                'dataCentersIPs'    => '150.0.0.1-150.0.0.255',
                'otherOperatorsIPs' => '192.168.14.21-192.168.14.30',
            ]));

        $response = $this->service->fetch();

        $this->assertSame('150.0.1.0-150.0.1.120', $response->get('endUsersIPs'));
        $this->assertSame('150.0.0.1-150.0.0.255', $response->get('dataCentersIPs'));
    }
}
