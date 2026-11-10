<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataV94Interface;

/**
 * SharedWebHosting update data for ShahkarDataCenter::v94()->update() ("Shahkar DC EndUser V9.4").
 * `dataCenterId` is required: V9.4 uses it to identify the service (it cannot be changed).
 * Send only the fields you want to change.
 */
class SharedWebHostingUpdateV94DTO implements ServiceDataV94Interface
{
    public function __construct(
        public readonly string  $dataCenterId,
        public readonly ?string $publicIps  = null,
        public readonly ?string $privateIps = null,
        public readonly ?string $urlList    = null,
        public readonly ?bool   $hasSSL     = null,
        public readonly ?bool   $hasIXP     = null,
        public readonly ?int    $bandwidth  = null,
        public readonly ?string $startDate  = null,
        public readonly ?string $endDate    = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'dataCenterId' => $this->dataCenterId,
            'publicIps'    => $this->publicIps,
            'privateIps'   => $this->privateIps,
            'urlList'      => $this->urlList,
            'hasSSL'       => $this->hasSSL !== null ? (int) $this->hasSSL : null,
            'hasIXP'       => $this->hasIXP !== null ? (int) $this->hasIXP : null,
            'bandwidth'    => $this->bandwidth,
            'startDate'    => $this->startDate,
            'endDate'      => $this->endDate,
        ], fn($v) => $v !== null);
    }
}
