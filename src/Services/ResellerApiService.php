<?php

namespace Shahkar\DataCenter\Services;

use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Contracts\ResellerApiInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Reseller\CustomerUpdateResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\LegalPersonResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\NaturalPersonResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\ResellerServiceDTO;
use Shahkar\DataCenter\DTOs\Reseller\ResellerServiceUpdateDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Support\RequestIdGenerator;

/**
 * Shahkar "Reseller Code" service — document v9.4, service type 30.
 *
 * Standalone from the Data Center service; it only needs the shared HTTP client.
 * Close is expressed as an update with "close": 1; transfer as an update with
 * "transfer": 1 and a "transferRequest" block; delete has its own endpoint.
 *
 * @see \Shahkar\DataCenter\Contracts\ResellerApiInterface
 */
class ResellerApiService implements ResellerApiInterface
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
        NaturalPersonResellerDTO $person,
        ResellerServiceDTO       $service,
        ?AddressDTO              $address = null,
        ?string                 $requestId = null,
    ): ApiResponse {
        return $this->register($person->toArray(), $service, $address, $requestId);
    }

    public function registerForLegalPerson(
        LegalPersonResellerDTO $person,
        ResellerServiceDTO     $service,
        ?AddressDTO            $address = null,
        ?string               $requestId = null,
    ): ApiResponse {
        return $this->register($person->toArray(), $service, $address, $requestId);
    }

    public function update(
        string                     $serviceId,
        string                     $serviceNumber,
        ?ResellerServiceUpdateDTO  $serviceUpdate = null,
        ?AddressUpdateDTO          $addressUpdate = null,
        ?CustomerUpdateResellerDTO $customerUpdate = null,
        ?string                    $requestId = null,
    ): ApiResponse {
        $payload = array_filter([
            ...$this->basePayload($requestId),
            'id'             => $serviceId,
            'serviceNumber'  => $serviceNumber,
            'customerUpdate' => $customerUpdate?->toArray(),
            'addressUpdate'  => $addressUpdate?->toArray(),
            'serviceUpdate'  => $serviceUpdate?->toArray(),
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_UPDATE, $payload);
    }

    public function transferToNaturalPerson(
        string                   $serviceId,
        string                   $serviceNumber,
        NaturalPersonResellerDTO $person,
        ?AddressDTO              $address = null,
        ?string                 $requestId = null,
    ): ApiResponse {
        return $this->transfer($serviceId, $serviceNumber, $person->toArray(), $address, $requestId);
    }

    public function transferToLegalPerson(
        string                 $serviceId,
        string                 $serviceNumber,
        LegalPersonResellerDTO $person,
        ?AddressDTO            $address = null,
        ?string               $requestId = null,
    ): ApiResponse {
        return $this->transfer($serviceId, $serviceNumber, $person->toArray(), $address, $requestId);
    }

    public function close(
        string  $serviceId,
        string  $serviceNumber,
        ?string $requestId = null,
    ): ApiResponse {
        $payload = array_filter([
            ...$this->basePayload($requestId),
            'id'            => $serviceId,
            'serviceNumber' => $serviceNumber,
            'close'         => 1,
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_UPDATE, $payload);
    }

    public function delete(
        string  $serviceId,
        string  $serviceNumber,
        ?string $requestId = null,
    ): ApiResponse {
        $payload = array_filter([
            ...$this->basePayload($requestId),
            'id'            => $serviceId,
            'serviceNumber' => $serviceNumber,
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_DELETE, $payload);
    }

    private function register(
        array              $person,
        ResellerServiceDTO $service,
        ?AddressDTO        $address,
        ?string            $requestId,
    ): ApiResponse {
        $payload = array_filter([
            ...$this->basePayload($requestId),
            ...$person,
            'address' => $address?->toArray(),
            'service' => $service->toArray(),
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_PUT, $payload);
    }

    private function transfer(
        string      $serviceId,
        string      $serviceNumber,
        array       $person,
        ?AddressDTO $address,
        ?string     $requestId,
    ): ApiResponse {
        $transferRequest = array_filter([
            ...$person,
            'address' => $address?->toArray(),
        ], fn($v) => $v !== null);

        $payload = array_filter([
            ...$this->basePayload($requestId),
            'id'              => $serviceId,
            'serviceNumber'   => $serviceNumber,
            'transfer'        => 1,
            'transferRequest' => $transferRequest,
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT_UPDATE, $payload);
    }

    /**
     * requestId plus the requesting operator's own reseller code (optional).
     */
    private function basePayload(?string $requestId): array
    {
        return array_filter([
            'requestId'    => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'resellerCode' => $this->resellerCode !== '' ? $this->resellerCode : null,
        ], fn($v) => $v !== null);
    }
}
