<?php

namespace Shahkar\DataCenter\Services;

use Shahkar\DataCenter\Contracts\DataCenterApiInterface;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Contracts\ServiceDataInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Support\RequestIdGenerator;

class DataCenterApiService implements DataCenterApiInterface
{
    private const ENDPOINT_PUT    = 'rest/shahkar/datacenter/put';
    private const ENDPOINT_UPDATE = 'rest/shahkar/datacenter/update';
    private const ENDPOINT_CLOSE  = 'rest/shahkar/datacenter/close';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $operatorId,
    ) {}

    /**
     * Register a new data center service for a natural person.
     * Call this twice:
     *   1st call: without otp → API sends OTP to subscriber
     *   2nd call: with otp populated in $person → API finalizes registration
     */
    public function registerForNaturalPerson(
        NaturalPersonDTO     $person,
        AddressDTO           $address,
        ServiceDataInterface $service,
        ?string              $requestId = null
    ): ApiResponse {
        $payload = array_merge(
            ['requestId' => $requestId ?? RequestIdGenerator::generate($this->operatorId)],
            $person->toArray(),
            ['address' => $address->toArray()],
            ['service' => $service->toArray()],
        );

        return $this->client->post(self::ENDPOINT_PUT, $payload);
    }

    /**
     * Register a new data center service for a legal person.
     * Call this twice:
     *   1st call: without otp/agentOtp → API sends two OTPs
     *   2nd call: with both otp and agentOtp populated in $person
     */
    public function registerForLegalPerson(
        LegalPersonDTO       $person,
        AddressDTO           $address,
        ServiceDataInterface $service,
        ?string              $requestId = null
    ): ApiResponse {
        $payload = array_merge(
            ['requestId' => $requestId ?? RequestIdGenerator::generate($this->operatorId)],
            $person->toArray(),
            ['address' => $address->toArray()],
            ['service' => $service->toArray()],
        );

        return $this->client->post(self::ENDPOINT_PUT, $payload);
    }

    /**
     * Update an existing service for a natural person.
     */
    public function updateForNaturalPerson(
        string               $serviceId,
        string               $serviceNumber,
        int                  $otp,
        ServiceDataInterface $serviceUpdate,
        ?AddressUpdateDTO    $addressUpdate = null,
        ?string              $requestId = null
    ): ApiResponse {
        $payload = array_filter([
            'requestId'     => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'id'            => $serviceId,
            'serviceNumber' => $serviceNumber,
            'otp'           => $otp,
            'addressUpdate' => $addressUpdate?->toArray(),
            'serviceUpdate' => $serviceUpdate->toArray(),
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_UPDATE, $payload);
    }

    /**
     * Update an existing service for a legal person.
     */
    public function updateForLegalPerson(
        string                $serviceId,
        string                $serviceNumber,
        int                   $otp,
        int                   $agentOtp,
        ServiceDataInterface  $serviceUpdate,
        ?AddressUpdateDTO     $addressUpdate = null,
        ?LegalPersonUpdateDTO $customerUpdate = null,
        ?string               $requestId = null
    ): ApiResponse {
        $payload = array_filter([
            'requestId'      => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'id'             => $serviceId,
            'serviceNumber'  => $serviceNumber,
            'otp'            => $otp,
            'agentOtp'       => $agentOtp,
            'customerUpdate' => $customerUpdate?->toArray(),
            'addressUpdate'  => $addressUpdate?->toArray(),
            'serviceUpdate'  => $serviceUpdate->toArray(),
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_UPDATE, $payload);
    }

    /**
     * Close (terminate) an existing data center service.
     */
    public function close(string $serviceId, ?string $requestId = null): ApiResponse
    {
        $payload = [
            'requestId' => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'id'        => $serviceId,
        ];

        return $this->client->post(self::ENDPOINT_CLOSE, $payload);
    }
}
