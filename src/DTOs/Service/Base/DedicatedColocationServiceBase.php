<?php

namespace Shahkar\DataCenter\DTOs\Service\Base;

use InvalidArgumentException;
use Shahkar\DataCenter\Contracts\ServiceDataInterface;
use Shahkar\DataCenter\Enums\DataCenterType;
use Shahkar\DataCenter\Enums\ServiceType;

/**
 * Registration fields for a DedicatedServer / Colocation service with split
 * public/private IPs. Identical in V9.4 and V1.2; use
 * DedicatedColocationServiceV94DTO or DedicatedColocationServiceV12DTO rather
 * than this class.
 *
 * Unlike the older ips-based DTO, `province` is required here, as both
 * documents mark it mandatory for this service type.
 */
abstract class DedicatedColocationServiceBase implements ServiceDataInterface
{
    public function __construct(
        public readonly string         $dataCenterId,
        public readonly string         $centerName,
        public readonly string         $dataCenterAddress,
        public readonly string         $province,
        public readonly string         $publicIps,
        public readonly int            $bandwidth,
        public readonly string         $startDate,
        public readonly string         $lat,
        public readonly string         $lon,
        public readonly int            $rowIndex,
        public readonly int            $racIndex,
        public readonly int            $unitIndex,
        public readonly DataCenterType $dataCenterType = DataCenterType::DedicatedServer,
        public readonly ?string        $privateIps     = null,
        public readonly ?string        $endDate        = null,
        public readonly ?bool          $hasIXP         = null,
        public readonly ?int           $units          = null,
    ) {
        if (!in_array($dataCenterType, [DataCenterType::DedicatedServer, DataCenterType::Colocation], true)) {
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
            'publicIps'         => $this->publicIps,
            'privateIps'        => $this->privateIps,
            'bandwidth'         => $this->bandwidth,
            'startDate'         => $this->startDate,
            'endDate'           => $this->endDate,
            'lat'               => $this->lat,
            'lon'               => $this->lon,
            'rowIndex'          => $this->rowIndex,
            'racIndex'          => $this->racIndex,
            'unitIndex'         => $this->unitIndex,
            'units'             => $this->units,
            'hasIXP'            => $this->hasIXP !== null ? (int) $this->hasIXP : null,
        ], fn($v) => $v !== null);
    }
}
