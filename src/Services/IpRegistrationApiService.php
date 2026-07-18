<?php

namespace Shahkar\DataCenter\Services;

use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Contracts\IpRegistrationApiInterface;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Support\IpRangeHelper;
use Shahkar\DataCenter\Support\RequestIdGenerator;

/**
 * Shahkar "IP Registration" (putIP) service — document v1.5.
 *
 * Standalone from the Data Center service; it only needs the shared HTTP client.
 *
 * @see \Shahkar\DataCenter\Contracts\IpRegistrationApiInterface
 */
class IpRegistrationApiService implements IpRegistrationApiInterface
{
    private const ENDPOINT_PUT      = 'rest/shahkar/putIP';
    private const ENDPOINT_TRUNCATE = 'rest/shahkar/truncateIP';
    private const ENDPOINT_FETCH    = 'rest/shahkar/fetchIP';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $operatorId,
    ) {}

    public function put(
        string|array $endUsersIPs,
        string|array $dataCentersIPs,
        string|array $otherOperatorsIPs,
        ?string      $requestId = null,
    ): ApiResponse {
        $payload = [
            'requestId'         => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'endUsersIPs'       => $this->normalize($endUsersIPs),
            'dataCentersIPs'    => $this->normalize($dataCentersIPs),
            'otherOperatorsIPs' => $this->normalize($otherOperatorsIPs),
        ];

        return $this->client->post(self::ENDPOINT_PUT, $payload);
    }

    public function truncate(?string $requestId = null): ApiResponse
    {
        return $this->client->post(self::ENDPOINT_TRUNCATE, [
            'requestId' => $requestId ?? RequestIdGenerator::generate($this->operatorId),
        ]);
    }

    public function fetch(?string $requestId = null): ApiResponse
    {
        return $this->client->post(self::ENDPOINT_FETCH, [
            'requestId' => $requestId ?? RequestIdGenerator::generate($this->operatorId),
        ]);
    }

    /**
     * Accept either a ready-made string or an array of ranges to format.
     *
     * @param string|array<string> $ips
     */
    private function normalize(string|array $ips): string
    {
        return is_array($ips) ? IpRangeHelper::format($ips) : $ips;
    }
}
