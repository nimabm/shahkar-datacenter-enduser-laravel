<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataV12Interface;

/**
 * CDN update data for ShahkarDataCenter::v12()->updateFor...Person() ("Shahkar DC EndUser V1.2").
 * V1.2 no longer takes `dataCenterId` on update; the service is identified by its `id` alone.
 * Send only the fields you want to change.
 */
class CdnUpdateV12DTO implements ServiceDataV12Interface
{
    public function __construct(
        public readonly ?string $publicIps  = null,
        public readonly ?string $privateIps = null,
        public readonly ?string $urlList    = null,
        public readonly ?bool   $hasSSL     = null,
        public readonly ?int    $bandwidth  = null,
        public readonly ?string $startDate  = null,
        public readonly ?string $endDate    = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'publicIps'    => $this->publicIps,
            'privateIps'   => $this->privateIps,
            'urlList'      => $this->urlList,
            'hasSSL'       => $this->hasSSL !== null ? (int) $this->hasSSL : null,
            'bandwidth'    => $this->bandwidth,
            'startDate'    => $this->startDate,
            'endDate'      => $this->endDate,
        ], fn($v) => $v !== null);
    }
}
