<?php

namespace Shahkar\DataCenter\Enums;

/**
 * Identity document types accepted by the Estelaam (identity inquiry) service.
 *
 * This service accepts a wider set than the Data Center flow, so it has its own
 * enum instead of {@see IdentificationType}.
 *
 * @see \Shahkar\DataCenter\Contracts\InquiryApiInterface
 */
enum InquiryIdentificationType: int
{
    case NationalCode = 0;  // natural person, Iranian (national code)
    case Passport     = 1;  // natural person, foreign
    case AmayeshCard  = 2;  // natural person, foreign (Amayesh card)
    case RefugeeCard  = 3;  // natural person, foreign (refugee card)
    case IdentityCard = 4;  // natural person, foreign (identity card)
    case NationalId   = 5;  // legal person, Iranian (national ID)
    case FidaId       = 6;  // legal person, foreign (FIDA dedicated ID)

    public function isLegal(): bool
    {
        return $this === self::NationalId || $this === self::FidaId;
    }

    public function isForeign(): bool
    {
        return in_array($this, [
            self::Passport,
            self::AmayeshCard,
            self::RefugeeCard,
            self::IdentityCard,
            self::FidaId,
        ], true);
    }
}
