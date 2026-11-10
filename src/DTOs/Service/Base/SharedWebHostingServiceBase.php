<?php

namespace Shahkar\DataCenter\DTOs\Service\Base;

use Shahkar\DataCenter\Contracts\ServiceDataInterface;
use Shahkar\DataCenter\Enums\DataCenterType;
use Shahkar\DataCenter\Enums\ServiceType;

/**
 * Registration fields for a SharedWebHosting service with split public/private
 * IPs. Identical in V9.4 and V1.2; use SharedWebHostingServiceV94DTO or
 * SharedWebHostingServiceV12DTO rather than this class.
 */
abstract class SharedWebHostingServiceBase implements ServiceDataInterface
{
    public function __construct(
        public readonly string  $dataCenterId,
        public readonly string  $publicIps,
        public readonly int     $bandwidth,
        public readonly string  $startDate,
        public readonly string  $urlList,
        public readonly ?string $privateIps = null,
        public readonly ?string $endDate    = null,
        public readonly ?bool   $hasSSL     = null,
        public readonly ?bool   $hasIXP     = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type'           => ServiceType::DataCenter->value,
            'dataCenterType' => DataCenterType::SharedWebHosting->value,
            'dataCenterId'   => $this->dataCenterId,
            'publicIps'      => $this->publicIps,
            'privateIps'     => $this->privateIps,
            'bandwidth'      => $this->bandwidth,
            'startDate'      => $this->startDate,
            'endDate'        => $this->endDate,
            'urlList'        => $this->urlList,
            'hasSSL'         => $this->hasSSL !== null ? (int) $this->hasSSL : null,
            'hasIXP'         => $this->hasIXP !== null ? (int) $this->hasIXP : null,
        ], fn($v) => $v !== null);
    }
}
