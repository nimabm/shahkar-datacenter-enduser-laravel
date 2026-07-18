<?php

namespace Shahkar\DataCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Shahkar\DataCenter\Support\ShahkarDataCenterManager;

/**
 * Version selection:
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiInterface|\Shahkar\DataCenter\Contracts\DataCenterApiV92Interface version(\Shahkar\DataCenter\Enums\ApiVersion|string $version)
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiInterface|\Shahkar\DataCenter\Contracts\DataCenterApiV92Interface default()
 *
 * Default version (configured via shahkar-datacenter.default_version) methods are
 * forwarded directly. With the default set to '9.2' these resolve to the V9.2 flow:
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse registerForNaturalPerson(\Shahkar\DataCenter\DTOs\Person\NaturalPersonV92DTO $person, \Shahkar\DataCenter\DTOs\Address\AddressDTO $address, \Shahkar\DataCenter\Contracts\ServiceDataInterface $service, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse registerForLegalPerson(\Shahkar\DataCenter\DTOs\Person\LegalPersonV92DTO $person, \Shahkar\DataCenter\DTOs\Address\AddressDTO $address, \Shahkar\DataCenter\Contracts\ServiceDataInterface $service, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse update(string $serviceId, string $serviceNumber, \Shahkar\DataCenter\Contracts\ServiceDataInterface $serviceUpdate, ?\Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO $addressUpdate = null, ?\Shahkar\DataCenter\DTOs\Person\CustomerUpdateV92DTO $customerUpdate = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse close(string $serviceId, string $serviceNumber, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse delete(string $serviceId, string $serviceNumber, ?string $requestId = null)
 *
 * To use the new web service v1.0 (OTP) flow explicitly, go through version('1.0'):
 * @see \Shahkar\DataCenter\Contracts\DataCenterApiInterface
 * @see \Shahkar\DataCenter\Support\ShahkarDataCenterManager
 */
class ShahkarDataCenter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShahkarDataCenterManager::class;
    }
}
