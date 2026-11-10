<?php

namespace Shahkar\DataCenter\Contracts;

/**
 * Service data accepted by the "Shahkar DC EndUser V9.4" flow.
 *
 * V9.4 replaced the single `ips` field with `publicIps` (required) and
 * `privateIps` (optional). Only the *V94DTO service classes implement this,
 * so the older `ips`-based DTOs cannot be passed to v94() by mistake.
 *
 * @see \Shahkar\DataCenter\Contracts\DataCenterApiV94Interface
 */
interface ServiceDataV94Interface extends ServiceDataInterface
{
}
