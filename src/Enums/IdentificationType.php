<?php

namespace Shahkar\DataCenter\Enums;

enum IdentificationType: int
{
    case NationalCode = 0;  // کد ملی - حقیقی
    case NationalId   = 5;  // شناسه ملی - حقوقی

    public function label(): string
    {
        return match($this) {
            self::NationalCode => 'کد ملی (شخص حقیقی)',
            self::NationalId   => 'شناسه ملی (شخص حقوقی)',
        };
    }

    public function isLegal(): bool
    {
        return $this === self::NationalId;
    }
}
