<?php

namespace Shahkar\DataCenter\Facades;

use Illuminate\Support\Facades\Facade;
use Shahkar\DataCenter\Contracts\IpRegistrationApiInterface;

/**
 * Facade for the standalone Shahkar "IP Registration" (putIP) service — v1.5.
 * Separate from the ShahkarDataCenter facade.
 *
 *   ShahkarIp::put($endUsersIPs, $dataCentersIPs, $otherOperatorsIPs);
 *   ShahkarIp::fetch();
 *   ShahkarIp::truncate();
 *
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse put(string|array $endUsersIPs, string|array $dataCentersIPs, string|array $otherOperatorsIPs, ?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse truncate(?string $requestId = null)
 * @method static \Shahkar\DataCenter\Http\Responses\ApiResponse fetch(?string $requestId = null)
 *
 * @see \Shahkar\DataCenter\Services\IpRegistrationApiService
 */
class ShahkarIp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return IpRegistrationApiInterface::class;
    }
}
