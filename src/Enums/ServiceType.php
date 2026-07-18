<?php

namespace Shahkar\DataCenter\Enums;

/**
 * Top-level Shahkar service types (the `type` field).
 */
enum ServiceType: int
{
    case ResellerCode = 30;
    case DataCenter   = 35;

    public function label(): string
    {
        return match ($this) {
            self::ResellerCode => 'Reseller Code',
            self::DataCenter   => 'Data Center',
        };
    }
}
