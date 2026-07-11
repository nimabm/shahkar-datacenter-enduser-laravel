<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataInterface;
use Shahkar\DataCenter\Enums\DataCenterType;
use Shahkar\DataCenter\Enums\ServiceType;

class VpsServiceDTO implements ServiceDataInterface
{
    public function __construct(
        public readonly string  $dataCenterId,
        public readonly string  $centerName,
        public readonly string  $dataCenterAddress,
        public readonly string  $ips,
        public readonly int     $bandwidth,
        public readonly string  $startDate,
        public readonly ?string $endDate   = null,
        public readonly ?string $province  = null,
        public readonly ?bool   $hasIXP    = null,
        public readonly ?string $urlList   = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type'               => ServiceType::DataCenter->value,
            'dataCenterType'     => DataCenterType::VPS->value,
            'dataCenterId'       => $this->dataCenterId,
            'centerName'         => $this->centerName,
            'dataCenterAddress'  => $this->dataCenterAddress,
            'province'           => $this->province,
            'ips'                => $this->ips,
            'bandwidth'          => $this->bandwidth,
            'startDate'          => $this->startDate,
            'endDate'            => $this->endDate,
            'hasIXP'             => $this->hasIXP !== null ? (int) $this->hasIXP : null,
            'urlList'            => $this->urlList,
        ], fn($v) => $v !== null);
    }
}
