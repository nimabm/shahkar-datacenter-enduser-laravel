<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataV12Interface;
use Shahkar\DataCenter\DTOs\Service\Base\VpsServiceBase;

/**
 * VPS registration data for ShahkarDataCenter::v12() ("Shahkar DC EndUser V1.2").
 * Sends `publicIps` (required) and `privateIps` (optional) instead of `ips`.
 */
class VpsServiceV12DTO extends VpsServiceBase implements ServiceDataV12Interface
{
}
