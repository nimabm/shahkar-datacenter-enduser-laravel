<?php

namespace Shahkar\DataCenter\DTOs\Reseller;

use Shahkar\DataCenter\Enums\ServiceType;
use InvalidArgumentException;

/**
 * The `service` block when registering a Reseller Code (type 30).
 *
 * `resellerCode` here is the code being registered — it becomes the service's
 * identifier (serviceNumber) for later update/transfer/close/delete. It is
 * separate from the top-level resellerCode (the registering operator's code).
 *
 * @see "Shahkar Reseller Code API V9.4"
 */
class ResellerServiceDTO
{
    public function __construct(
        public readonly string  $resellerCode,
        public readonly string  $province,
        public readonly ?bool   $ipStatic = null,
        public readonly ?string $rangeIps = null,
    ) {
        if ($this->ipStatic === true && ($this->rangeIps === null || $this->rangeIps === '')) {
            throw new InvalidArgumentException(
                'rangeIps is required when ipStatic is true.'
            );
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'type'         => ServiceType::ResellerCode->value,
            'resellerCode' => $this->resellerCode,
            'province'     => $this->province,
            'ipStatic'     => $this->ipStatic !== null ? (int) $this->ipStatic : null,
            'rangeIps'     => $this->rangeIps,
        ], fn($v) => $v !== null);
    }
}
