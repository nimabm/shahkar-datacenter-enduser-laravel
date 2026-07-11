<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataInterface;

class SharedWebHostingUpdateDTO implements ServiceDataInterface
{
    public function __construct(
        public readonly string  $dataCenterId,
        public readonly ?string $ips       = null,
        public readonly ?string $urlList   = null,
        public readonly ?bool   $hasSSL    = null,
        public readonly ?bool   $hasIXP    = null,
        public readonly ?int    $bandwidth = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate   = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'dataCenterId' => $this->dataCenterId,
            'ips'          => $this->ips,
            'urlList'      => $this->urlList,
            'hasSSL'       => $this->hasSSL !== null ? (int) $this->hasSSL : null,
            'hasIXP'       => $this->hasIXP !== null ? (int) $this->hasIXP : null,
            'bandwidth'    => $this->bandwidth,
            'startDate'    => $this->startDate,
            'endDate'      => $this->endDate,
        ], fn($v) => $v !== null);
    }
}
