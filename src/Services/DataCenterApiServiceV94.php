<?php

namespace Shahkar\DataCenter\Services;

use Shahkar\DataCenter\Contracts\DataCenterApiV94Interface;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Contracts\ServiceDataV94Interface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\CustomerUpdateV92DTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV92DTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Support\RequestIdGenerator;

/**
 * "Shahkar DC EndUser V9.4" flow — single request, no OTP.
 *
 * Same endpoints and request shape as V9.2 (close is an update with
 * "close": 1, delete has its own endpoint). Service data carries
 * `publicIps`/`privateIps` instead of `ips`.
 *
 * @see "Shahkar DC EndUser V9.4"
 */
class DataCenterApiServiceV94 implements DataCenterApiV94Interface
{
    private const ENDPOINT_PUT    = 'rest/shahkar/put';
    private const ENDPOINT_UPDATE = 'rest/shahkar/update';
    private const ENDPOINT_DELETE = 'rest/shahkar/delete';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $operatorId,
        private readonly string $resellerCode = '',
    ) {}

    public function registerForNaturalPerson(
        NaturalPersonV92DTO     $person,
        AddressDTO              $address,
        ServiceDataV94Interface $service,
        ?string                 $requestId = null
    ): ApiResponse {
        return $this->register($person->toArray(), $address, $service, $requestId);
    }

    public function registerForLegalPerson(
        LegalPersonV92DTO       $person,
        AddressDTO              $address,
        ServiceDataV94Interface $service,
        ?string                 $requestId = null
    ): ApiResponse {
        return $this->register($person->toArray(), $address, $service, $requestId);
    }

    public function update(
        string                  $serviceId,
        string                  $serviceNumber,
        ServiceDataV94Interface $serviceUpdate,
        ?AddressUpdateDTO       $addressUpdate = null,
        ?CustomerUpdateV92DTO   $customerUpdate = null,
        ?string                 $requestId = null
    ): ApiResponse {
        $payload = array_filter([
            ...$this->basePayload($requestId),
            'id'             => $serviceId,
            'serviceNumber'  => $serviceNumber,
            'customerUpdate' => $customerUpdate?->toArray(),
            'addressUpdate'  => $addressUpdate?->toArray(),
            'serviceUpdate'  => $serviceUpdate->toArray(),
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_UPDATE, $payload);
    }

    public function close(
        string  $serviceId,
        string  $serviceNumber,
        ?string $requestId = null
    ): ApiResponse {
        $payload = [
            ...$this->basePayload($requestId),
            'id'            => $serviceId,
            'serviceNumber' => $serviceNumber,
            'close'         => 1,
        ];

        return $this->client->post(self::ENDPOINT_UPDATE, $payload);
    }

    public function delete(
        string  $serviceId,
        string  $serviceNumber,
        ?string $requestId = null
    ): ApiResponse {
        $payload = [
            ...$this->basePayload($requestId),
            'id'            => $serviceId,
            'serviceNumber' => $serviceNumber,
        ];

        return $this->client->post(self::ENDPOINT_DELETE, $payload);
    }

    private function register(
        array                   $person,
        AddressDTO              $address,
        ServiceDataV94Interface $service,
        ?string                 $requestId
    ): ApiResponse {
        $payload = array_merge(
            $this->basePayload($requestId),
            $person,
            ['address' => $address->toArray()],
            ['service' => $service->toArray()],
        );

        return $this->client->post(self::ENDPOINT_PUT, $payload);
    }

    /**
     * Fields common to every V9.4 request: a requestId and the reseller code.
     */
    private function basePayload(?string $requestId): array
    {
        return array_filter([
            'requestId'    => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'resellerCode' => $this->resellerCode !== '' ? $this->resellerCode : null,
        ], fn($v) => $v !== null);
    }
}
