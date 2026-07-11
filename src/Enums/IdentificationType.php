<?php

namespace Shahkar\DataCenter\Enums;

enum IdentificationType: int
{
    case NationalCode = 0;  // National Code - natural person
    case NationalId   = 5;  // National ID - legal person

    public function label(): string
    {
        return match($this) {
            self::NationalCode => 'National Code (natural person)',
            self::NationalId   => 'National ID (legal person)',
        };
    }

    public function isLegal(): bool
    {
        return $this === self::NationalId;
    }
}
