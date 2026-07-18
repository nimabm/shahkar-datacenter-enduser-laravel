<?php

namespace Shahkar\DataCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Shahkar\DataCenter\Contracts\ResellerApiInterface;

/**
 * Facade for the standalone Shahkar "Reseller Code" service — v9.4 (type 30).
 * Separate from the ShahkarDataCenter facade.
 *
 *   ShahkarReseller::registerForNaturalPerson($person, $service, $address);
 *   ShahkarReseller::transferToLegalPerson($id, $serviceNumber, $newOwner);
 *   ShahkarReseller::close($id, $serviceNumber);
 *
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse registerForNaturalPerson(\Shahkar\DataCenter\DTOs\Reseller\NaturalPersonResellerDTO $person, \Shahkar\DataCenter\DTOs\Reseller\ResellerServiceDTO $service, ?\Shahkar\DataCenter\DTOs\Address\AddressDTO $address = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse registerForLegalPerson(\Shahkar\DataCenter\DTOs\Reseller\LegalPersonResellerDTO $person, \Shahkar\DataCenter\DTOs\Reseller\ResellerServiceDTO $service, ?\Shahkar\DataCenter\DTOs\Address\AddressDTO $address = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse update(string $serviceId, string $serviceNumber, ?\Shahkar\DataCenter\DTOs\Reseller\ResellerServiceUpdateDTO $serviceUpdate = null, ?\Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO $addressUpdate = null, ?\Shahkar\DataCenter\DTOs\Reseller\CustomerUpdateResellerDTO $customerUpdate = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse transferToNaturalPerson(string $serviceId, string $serviceNumber, \Shahkar\DataCenter\DTOs\Reseller\NaturalPersonResellerDTO $person, ?\Shahkar\DataCenter\DTOs\Address\AddressDTO $address = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse transferToLegalPerson(string $serviceId, string $serviceNumber, \Shahkar\DataCenter\DTOs\Reseller\LegalPersonResellerDTO $person, ?\Shahkar\DataCenter\DTOs\Address\AddressDTO $address = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse close(string $serviceId, string $serviceNumber, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse delete(string $serviceId, string $serviceNumber, ?string $requestId = null)
 *
 * @see \Shahkar\DataCenter\Services\ResellerApiService
 */
class ShahkarReseller extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ResellerApiInterface::class;
    }
}
