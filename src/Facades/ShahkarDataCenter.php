<?php

namespace Shahkar\DataCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Shahkar\DataCenter\Support\ShahkarDataCenterManager;

/**
 * Pick a version, then call methods on the returned contract:
 *
 *   ShahkarDataCenter::v92()->registerForNaturalPerson(...); // v9.2, single-step, no OTP
 *   ShahkarDataCenter::v1()->registerForNaturalPerson(...);  // v1.0, two-step OTP
 *
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV92Interface v92()
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV1Interface v1()
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV1Interface|\Shahkar\DataCenter\Contracts\DataCenterApiV92Interface version(\Shahkar\DataCenter\Enums\ApiVersion|string $version)
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV1Interface|\Shahkar\DataCenter\Contracts\DataCenterApiV92Interface default()
 *
 * @see \Shahkar\DataCenter\Support\ShahkarDataCenterManager
 */
class ShahkarDataCenter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShahkarDataCenterManager::class;
    }
}
