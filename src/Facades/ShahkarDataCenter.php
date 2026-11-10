<?php

namespace Shahkar\DataCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Shahkar\DataCenter\Support\ShahkarDataCenterManager;

/**
 * Pick a version, then call methods on the returned contract:
 *
 *   ShahkarDataCenter::v94()->registerForNaturalPerson(...); // v9.4, single-step, publicIps/privateIps
 *   ShahkarDataCenter::v12()->registerForNaturalPerson(...); // v1.2, two-step OTP, publicIps/privateIps
 *   ShahkarDataCenter::v92()->registerForNaturalPerson(...); // v9.2, single-step, ips
 *   ShahkarDataCenter::v1()->registerForNaturalPerson(...);  // v1.0, two-step OTP, ips
 *
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV94Interface v94()
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV12Interface v12()
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV92Interface v92()
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV1Interface v1()
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV1Interface|\Shahkar\DataCenter\Contracts\DataCenterApiV12Interface|\Shahkar\DataCenter\Contracts\DataCenterApiV92Interface|\Shahkar\DataCenter\Contracts\DataCenterApiV94Interface version(\Shahkar\DataCenter\Enums\ApiVersion|string $version)
 * @method static \Shahkar\DataCenter\Contracts\DataCenterApiV1Interface|\Shahkar\DataCenter\Contracts\DataCenterApiV12Interface|\Shahkar\DataCenter\Contracts\DataCenterApiV92Interface|\Shahkar\DataCenter\Contracts\DataCenterApiV94Interface default()
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
