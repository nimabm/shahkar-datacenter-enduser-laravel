<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataInterface;

class VpsUpdateDTO implements ServiceDataInterface
{
    public function __construct(
        public readonly string  $dataCenterId,
        public readonly ?string $dataCenterAddress = null,
        public readonly ?string $province          = null,
        public readonly ?string $ips               = null,
        public readonly ?int    $bandwidth         = null,
        public readonly ?bool   $hasIXP            = null,
        public readonly ?string $startDate         = null,
        public readonly ?string $endDate           = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'dataCenterId'      => $this->dataCenterId,
            'dataCenterAddress' => $this->dataCenterAddress,
            'province'          => $this->province,
            'ips'               => $this->ips,
            'bandwidth'         => $this->bandwidth,
            'hasIXP'            => $this->hasIXP !== null ? (int) $this->hasIXP : null,
            'startDate'         => $this->startDate,
            'endDate'           => $this->endDate,
        ], fn($v) => $v !== null);
    }
}
