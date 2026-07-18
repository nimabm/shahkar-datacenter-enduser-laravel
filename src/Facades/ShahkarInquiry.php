<?php

namespace Shahkar\DataCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Shahkar\DataCenter\Contracts\InquiryApiInterface;

/**
 * Facade for the standalone Shahkar "Estelaam" identity-inquiry service — v1.4.
 * Separate from the ShahkarDataCenter facade.
 *
 *   $r = ShahkarInquiry::verifyNaturalPerson($person);
 *   $verified = $r->get('response') === 200;
 *
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse verifyNaturalPerson(\Shahkar\DataCenter\DTOs\Inquiry\NaturalPersonInquiryDTO $person, ?\Shahkar\DataCenter\DTOs\Address\AddressDTO $address = null, ?int $serviceType = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse verifyLegalPerson(\Shahkar\DataCenter\DTOs\Inquiry\LegalPersonInquiryDTO $person, ?\Shahkar\DataCenter\DTOs\Address\AddressDTO $address = null, ?int $serviceType = null, ?string $requestId = null)
 *
 * @see \Shahkar\DataCenter\Services\InquiryApiService
 */
class ShahkarInquiry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return InquiryApiInterface::class;
    }
}
