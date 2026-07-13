<?php

namespace Shahkar\DataCenter\Services;

use Shahkar\DataCenter\Contracts\CryptoServiceInterface;
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
    // Outer transport endpoints.
    private const EP_REGISTER_KEY = '/dc/register-key';
    private const EP_SEND         = '/dc/send';
    private const EP_UPDATE       = '/dc/update';
    private const EP_DELETE       = '/dc/delete';
    private const EP_STATUS       = '/dc/status/';

    // Inner (signed) API paths.
    private const PATH_PUT    = '/rest/shahkar/datacenter/put';
    private const PATH_UPDATE = '/rest/shahkar/datacenter/update';
    private const PATH_DELETE = '/rest/shahkar/datacenter/delete';

    public function __construct(
        private readonly HttpClientInterface   $client,
        private readonly CryptoServiceInterface $crypto,
        private readonly string                $providerCode,
    ) {}

    public function registerKey(string $otp = ''): ApiResponse
    {
        return $this->client->post(self::EP_REGISTER_KEY, [
            'clientId'  => $this->crypto->getClientId(),
            'publicKey' => $this->crypto->getClientPublicKey(),
            'otp'       => $otp,
        ]);
    }

    public function registerForNaturalPerson(
        NaturalPersonDTO     $person,
        AddressDTO           $address,
        ServiceDataInterface $service,
        ?string              $requestId = null
    ): ApiResponse {
        $data = array_merge(
            $this->baseData($requestId),
            $person->toArray(),
            ['address' => $address->toArray()],
            ['service' => $service->toArray()],
        );

        return $this->sendEncrypted(self::EP_SEND, self::PATH_PUT, $data);
    }

    public function registerForLegalPerson(
        LegalPersonDTO       $person,
        AddressDTO           $address,
        ServiceDataInterface $service,
        ?string              $requestId = null
    ): ApiResponse {
        $data = array_merge(
            $this->baseData($requestId),
            $person->toArray(),
            ['address' => $address->toArray()],
            ['service' => $service->toArray()],
        );

        return $this->sendEncrypted(self::EP_SEND, self::PATH_PUT, $data);
    }

    public function updateForNaturalPerson(
        string               $serviceId,
        string               $serviceNumber,
        ServiceDataInterface $serviceUpdate,
        ?int                 $otp = null,
        ?AddressUpdateDTO    $addressUpdate = null,
        ?string              $requestId = null
    ): ApiResponse {
        $data = array_filter([
            ...$this->baseData($requestId),
            'id'            => $serviceId,
            'serviceNumber' => $serviceNumber,
            'otp'           => $otp,
            'addressUpdate' => $addressUpdate?->toArray(),
            'serviceUpdate' => $serviceUpdate->toArray(),
        ], fn ($v) => $v !== null);

        return $this->sendEncrypted(self::EP_UPDATE, self::PATH_UPDATE, $data);
    }

    public function updateForLegalPerson(
        string                $serviceId,
        string                $serviceNumber,
        ServiceDataInterface  $serviceUpdate,
        ?int                  $otp = null,
        ?int                  $agentOtp = null,
        ?AddressUpdateDTO     $addressUpdate = null,
        ?LegalPersonUpdateDTO $customerUpdate = null,
        ?string               $requestId = null
    ): ApiResponse {
        $data = array_filter([
            ...$this->baseData($requestId),
            'id'             => $serviceId,
            'serviceNumber'  => $serviceNumber,
            'otp'            => $otp,
            'agentOtp'       => $agentOtp,
            'customerUpdate' => $customerUpdate?->toArray(),
            'addressUpdate'  => $addressUpdate?->toArray(),
            'serviceUpdate'  => $serviceUpdate->toArray(),
        ], fn ($v) => $v !== null);

        return $this->sendEncrypted(self::EP_UPDATE, self::PATH_UPDATE, $data);
    }

    public function delete(string $serviceId, ?string $requestId = null): ApiResponse
    {
        $data = array_merge($this->baseData($requestId), ['id' => $serviceId]);

        return $this->sendEncrypted(self::EP_DELETE, self::PATH_DELETE, $data);
    }

    public function close(string $serviceId, ?string $requestId = null): ApiResponse
    {
        return $this->delete($serviceId, $requestId);
    }

    public function checkStatus(string $trackingId): ApiResponse
    {
        $response = $this->client->get(self::EP_STATUS . rawurlencode($trackingId));

        $encrypted = $response->body['data']['responseBody']
            ?? $response->body['responseBody']
            ?? null;

        if (is_string($encrypted) && $encrypted !== '') {
            return $response->withDecrypted($this->crypto->decryptAndVerify($encrypted));
        }

        return $response;
    }

    /**
     * @param  array<string,mixed> $data
     */
    private function sendEncrypted(string $endpoint, string $path, array $data): ApiResponse
    {
        $encrypted = $this->crypto->encryptAndSign($data, $path);

        $response = $this->client->post($endpoint, ['signedEncryptedPayload' => $encrypted]);

        return $response->withRequestId($data['requestId']);
    }

    /**
     * @return array{requestId:string}
     */
    private function baseData(?string $requestId): array
    {
        return ['requestId' => $requestId ?? RequestIdGenerator::generate($this->providerCode)];
    }
}
