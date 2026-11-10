<?php

namespace Shahkar\DataCenter\Services;

use Shahkar\DataCenter\Contracts\DataCenterApiV12Interface;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Contracts\ServiceDataV12Interface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV1DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV1DTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Support\RequestIdGenerator;

/**
 * "Shahkar DC EndUser V1.2" flow — new authentication, two-step OTP.
 *
 * Same endpoints as V1.0. Service data carries `publicIps`/`privateIps`
 * instead of `ips`, and update identifies the service by `id` only.
 *
 * @see "Shahkar DC EndUser V1.2"
 */
class DataCenterApiServiceV12 implements DataCenterApiV12Interface
{
    private const ENDPOINT_PUT    = 'rest/shahkar/datacenter/put';
    private const ENDPOINT_UPDATE = 'rest/shahkar/datacenter/update';
    private const ENDPOINT_CLOSE  = 'rest/shahkar/datacenter/close';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $operatorId,
    ) {}

    public function registerForNaturalPerson(
        NaturalPersonV1DTO      $person,
        AddressDTO              $address,
        ServiceDataV12Interface $service,
        ?string                 $requestId = null
    ): ApiResponse {
        return $this->register($person->toArray(), $address, $service, $requestId);
    }

    public function registerForLegalPerson(
        LegalPersonV1DTO        $person,
        AddressDTO              $address,
        ServiceDataV12Interface $service,
        ?string                 $requestId = null
    ): ApiResponse {
        return $this->register($person->toArray(), $address, $service, $requestId);
    }

    public function updateForNaturalPerson(
        string                  $serviceId,
        ServiceDataV12Interface $serviceUpdate,
        ?AddressUpdateDTO       $addressUpdate = null,
        ?int                    $otp = null,
        ?string                 $requestId = null
    ): ApiResponse {
        return $this->update($serviceId, $serviceUpdate, $addressUpdate, [
            'otp' => $otp,
        ], $requestId);
    }

    public function updateForLegalPerson(
        string                  $serviceId,
        ServiceDataV12Interface $serviceUpdate,
        ?AddressUpdateDTO       $addressUpdate = null,
        ?int                    $otp = null,
        ?int                    $agentOtp = null,
        ?string                 $requestId = null
    ): ApiResponse {
        return $this->update($serviceId, $serviceUpdate, $addressUpdate, [
            'otp'      => $otp,
            'agentOtp' => $agentOtp,
        ], $requestId);
    }

    public function close(string $serviceId, ?string $requestId = null): ApiResponse
    {
        $payload = [
            'requestId' => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'id'        => $serviceId,
        ];

        return $this->client->post(self::ENDPOINT_CLOSE, $payload);
    }

    private function register(
        array                   $person,
        AddressDTO              $address,
        ServiceDataV12Interface $service,
        ?string                 $requestId
    ): ApiResponse {
        $payload = array_merge(
            ['requestId' => $requestId ?? RequestIdGenerator::generate($this->operatorId)],
            $person,
            ['address' => $address->toArray()],
            ['service' => $service->toArray()],
        );

        return $this->client->post(self::ENDPOINT_PUT, $payload);
    }

    /**
     * @param array<string, int|null> $otps
     */
    private function update(
        string                  $serviceId,
        ServiceDataV12Interface $serviceUpdate,
        ?AddressUpdateDTO       $addressUpdate,
        array                   $otps,
        ?string                 $requestId
    ): ApiResponse {
        // V1.2 update DTOs have no required field, so an empty serviceUpdate
        // is possible (e.g. an address-only change). Omit it rather than send
        // a JSON array where Shahkar expects an object.
        $payload = array_filter([
            'requestId'     => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'id'            => $serviceId,
            ...$otps,
            'addressUpdate' => $addressUpdate?->toArray(),
            'serviceUpdate' => $serviceUpdate->toArray() ?: null,
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_UPDATE, $payload);
    }
}
