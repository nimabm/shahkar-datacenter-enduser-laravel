<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataV12Interface;
use Shahkar\DataCenter\DTOs\Service\Base\CdnServiceBase;

/**
 * CDN registration data for ShahkarDataCenter::v12() ("Shahkar DC EndUser V1.2").
 * Sends `publicIps` (required) and `privateIps` (optional) instead of `ips`.
 */
class CdnServiceV12DTO extends CdnServiceBase implements ServiceDataV12Interface
{
}
