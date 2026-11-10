<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataV94Interface;
use Shahkar\DataCenter\DTOs\Service\Base\CdnServiceBase;

/**
 * CDN registration data for ShahkarDataCenter::v94() ("Shahkar DC EndUser V9.4").
 * Sends `publicIps` (required) and `privateIps` (optional) instead of `ips`.
 */
class CdnServiceV94DTO extends CdnServiceBase implements ServiceDataV94Interface
{
}
