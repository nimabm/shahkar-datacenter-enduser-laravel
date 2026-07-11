<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataInterface;
use Shahkar\DataCenter\Enums\DataCenterType;
use Shahkar\DataCenter\Enums\ServiceType;

class SharedWebHostingServiceDTO implements ServiceDataInterface
{
    public function __construct(
        public readonly string $dataCenterId,
        public readonly string $ips,
        public readonly int    $bandwidth,
        public readonly string $startDate,
        public readonly string $urlList,
        public readonly ?string $endDate  = null,
        public readonly ?bool   $hasSSL   = null,
        public readonly ?bool   $hasIXP   = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type'           => ServiceType::DataCenter->value,
            'dataCenterType' => DataCenterType::SharedWebHosting->value,
            'dataCenterId'   => $this->dataCenterId,
            'ips'            => $this->ips,
            'bandwidth'      => $this->bandwidth,
            'startDate'      => $this->startDate,
            'endDate'        => $this->endDate,
            'urlList'        => $this->urlList,
            'hasSSL'         => $this->hasSSL !== null ? (int) $this->hasSSL : null,
            'hasIXP'         => $this->hasIXP !== null ? (int) $this->hasIXP : null,
        ], fn($v) => $v !== null);
    }
}
