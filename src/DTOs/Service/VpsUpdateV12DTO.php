<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataV12Interface;

/**
 * VPS update data for ShahkarDataCenter::v12()->updateFor...Person() ("Shahkar DC EndUser V1.2").
 * V1.2 no longer takes `dataCenterId` on update; the service is identified by its `id` alone.
 * Send only the fields you want to change.
 */
class VpsUpdateV12DTO implements ServiceDataV12Interface
{
    public function __construct(
        public readonly ?string $dataCenterAddress = null,
        public readonly ?string $province          = null,
        public readonly ?string $publicIps         = null,
        public readonly ?string $privateIps        = null,
        public readonly ?int    $bandwidth         = null,
        public readonly ?bool   $hasIXP            = null,
        public readonly ?string $startDate         = null,
        public readonly ?string $endDate           = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'dataCenterAddress' => $this->dataCenterAddress,
            'province'          => $this->province,
            'publicIps'         => $this->publicIps,
            'privateIps'        => $this->privateIps,
            'bandwidth'         => $this->bandwidth,
            'hasIXP'            => $this->hasIXP !== null ? (int) $this->hasIXP : null,
            'startDate'         => $this->startDate,
            'endDate'           => $this->endDate,
        ], fn($v) => $v !== null);
    }
}
