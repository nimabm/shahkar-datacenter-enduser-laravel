<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataV94Interface;
use Shahkar\DataCenter\DTOs\Service\Base\SharedWebHostingServiceBase;

/**
 * SharedWebHosting registration data for ShahkarDataCenter::v94() ("Shahkar DC EndUser V9.4").
 * Sends `publicIps` (required) and `privateIps` (optional) instead of `ips`.
 */
class SharedWebHostingServiceV94DTO extends SharedWebHostingServiceBase implements ServiceDataV94Interface
{
}
