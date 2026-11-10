<?php

namespace Shahkar\DataCenter\Contracts;

/**
 * Service data accepted by the "Shahkar DC EndUser V1.2" (new auth, OTP) flow.
 *
 * V1.2 replaced the single `ips` field with `publicIps` (required) and
 * `privateIps` (optional), and no longer takes `dataCenterId` on update. Only
 * the *V12DTO service classes implement this, so the older `ips`-based DTOs
 * cannot be passed to v12() by mistake.
 *
 * @see \Shahkar\DataCenter\Contracts\DataCenterApiV12Interface
 */
interface ServiceDataV12Interface extends ServiceDataInterface
{
}
