<?php

namespace Shahkar\DataCenter\Enums;

enum DataCenterType: int
{
    case VPS              = 11;
    case DedicatedServer  = 12;
    case Colocation       = 13;
    case SharedWebHosting = 14;
    case CDN              = 15;

    public function label(): string
    {
        return match($this) {
            self::VPS              => 'Virtual Private Server',
            self::DedicatedServer  => 'Dedicated Server',
            self::Colocation       => 'Colocation',
            self::SharedWebHosting => 'Shared Web Hosting',
            self::CDN              => 'Content Delivery Network',
        };
    }
}
