<?php

namespace Shahkar\DataCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;

/**
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse registerKey(string $otp = '')
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse registerForNaturalPerson(\Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO $person, \Shahkar\DataCenter\DTOs\Address\AddressDTO $address, \Shahkar\DataCenter\Contracts\ServiceDataInterface $service, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse registerForLegalPerson(\Shahkar\DataCenter\DTOs\Person\LegalPersonDTO $person, \Shahkar\DataCenter\DTOs\Address\AddressDTO $address, \Shahkar\DataCenter\Contracts\ServiceDataInterface $service, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse updateForNaturalPerson(string $serviceId, string $serviceNumber, \Shahkar\DataCenter\Contracts\ServiceDataInterface $serviceUpdate, ?int $otp = null, ?\Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO $addressUpdate = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse updateForLegalPerson(string $serviceId, string $serviceNumber, \Shahkar\DataCenter\Contracts\ServiceDataInterface $serviceUpdate, ?int $otp = null, ?int $agentOtp = null, ?\Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO $addressUpdate = null, ?\Shahkar\DataCenter\DTOs\Person\LegalPersonUpdateDTO $customerUpdate = null, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse delete(string $serviceId, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse close(string $serviceId, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse checkStatus(string $trackingId)
 *
 * @see \Shahkar\DataCenter\Services\DataCenterApiService
 */
class ShahkarDataCenter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DataCenterApiInterface::class;
    }
}
