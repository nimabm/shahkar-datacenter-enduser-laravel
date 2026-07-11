<?php

namespace Shahkar\DataCenter\DTOs\Service;

use Shahkar\DataCenter\Contracts\ServiceDataInterface;
use Shahkar\DataCenter\Enums\DataCenterType;
use Shahkar\DataCenter\Enums\ServiceType;
use InvalidArgumentException;

class DedicatedColocationServiceDTO implements ServiceDataInterface
{
    public function __construct(
        public readonly string       $dataCenterId,
        public readonly string       $centerName,
        public readonly string       $dataCenterAddress,
        public readonly string       $ips,
        public readonly int          $bandwidth,
        public readonly string       $startDate,
        public readonly string       $lat,
        public readonly string       $lon,
        public readonly int          $rowIndex,
        public readonly int          $racIndex,
        public readonly int          $unitIndex,
        public readonly DataCenterType $dataCenterType = DataCenterType::DedicatedServer,
        public readonly ?string      $endDate  = null,
        public readonly ?string      $province = null,
        public readonly ?bool        $hasIXP   = null,
        public readonly ?int         $units    = null,
    ) {
        if (!in_array($dataCenterType, [DataCenterType::DedicatedServer, DataCenterType::Colocation])) {
            throw new InvalidArgumentException(
                'dataCenterType must be DedicatedServer or Colocation.'
            );
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'type'              => ServiceType::DataCenter->value,
            'dataCenterType'    => $this->dataCenterType->value,
            'dataCenterId'      => $this->dataCenterId,
            'centerName'        => $this->centerName,
            'dataCenterAddress' => $this->dataCenterAddress,
            'province'          => $this->province,
            'ips'               => $this->ips,
            'bandwidth'         => $this->bandwidth,
            'startDate'         => $this->startDate,
            'endDate'           => $this->endDate,
            'lat'               => $this->lat,
            'lon'               => $this->lon,
            'rowtIndex'         => $this->rowIndex, // API uses "rowtIndex" (typo in spec)
            'racIndex'          => $this->racIndex,
            'unitIndex'         => $this->unitIndex,
            'units'             => $this->units,
            'hasIXP'            => $this->hasIXP !== null ? (int) $this->hasIXP : null,
        ], fn($v) => $v !== null);
    }
}
