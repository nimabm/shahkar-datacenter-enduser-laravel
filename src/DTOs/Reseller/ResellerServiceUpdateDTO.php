<?php

namespace Shahkar\DataCenter\DTOs\Reseller;

/**
 * The `serviceUpdate` block for a Reseller Code update. Only non-null fields are
 * sent, so change just what you need.
 *
 * @see "Shahkar Reseller Code API V9.4"
 */
class ResellerServiceUpdateDTO
{
    public function __construct(
        public readonly ?string $province = null,
        public readonly ?bool   $ipStatic = null,
        public readonly ?string $rangeIps = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'province' => $this->province,
            'ipStatic' => $this->ipStatic !== null ? (int) $this->ipStatic : null,
            'rangeIps' => $this->rangeIps,
        ], fn($v) => $v !== null);
    }
}
