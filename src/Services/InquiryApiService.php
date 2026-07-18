<?php

namespace Shahkar\DataCenter\Services;

use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Contracts\InquiryApiInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Inquiry\LegalPersonInquiryDTO;
use Shahkar\DataCenter\DTOs\Inquiry\NaturalPersonInquiryDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Support\RequestIdGenerator;

/**
 * Shahkar "Estelaam" identity-inquiry service — document v1.4.
 *
 * Standalone from the Data Center service; it only needs the shared HTTP client.
 *
 * @see \Shahkar\DataCenter\Contracts\InquiryApiInterface
 */
class InquiryApiService implements InquiryApiInterface
{
    private const ENDPOINT = 'rest/shahkar/estelaam';

    /** estelaamType 0 = identity verification (the only documented type). */
    private const ESTELAAM_TYPE_IDENTITY = 0;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $operatorId,
    ) {}

    public function verifyNaturalPerson(
        NaturalPersonInquiryDTO $person,
        ?AddressDTO             $address = null,
        ?int                    $serviceType = null,
        ?string                 $requestId = null,
    ): ApiResponse {
        return $this->inquire($person->toArray(), $address, $serviceType, $requestId);
    }

    public function verifyLegalPerson(
        LegalPersonInquiryDTO $person,
        ?AddressDTO           $address = null,
        ?int                  $serviceType = null,
        ?string               $requestId = null,
    ): ApiResponse {
        return $this->inquire($person->toArray(), $address, $serviceType, $requestId);
    }

    private function inquire(
        array       $person,
        ?AddressDTO $address,
        ?int        $serviceType,
        ?string     $requestId,
    ): ApiResponse {
        $payload = array_filter([
            'requestId'    => $requestId ?? RequestIdGenerator::generate($this->operatorId),
            'estelaamType' => self::ESTELAAM_TYPE_IDENTITY,
            ...$person,
            'address'      => $address?->toArray(),
            'service'      => $serviceType !== null ? ['serviceType' => $serviceType] : null,
        ], fn($v) => $v !== null);

        return $this->client->post(self::ENDPOINT, $payload);
    }
}
