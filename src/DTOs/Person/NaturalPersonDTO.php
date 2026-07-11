<?php

namespace Shahkar\DataCenter\DTOs\Person;

use Shahkar\DataCenter\Enums\IdentificationType;

class NaturalPersonDTO
{
    public function __construct(
        public readonly string $identificationNo,
        public readonly ?int $otp = null,
        public readonly IdentificationType $identificationType = IdentificationType::NationalCode,
        public readonly ?string $nationality = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'identificationType' => $this->identificationType->value,
            'identificationNo'   => $this->identificationNo,
            'nationality'        => $this->nationality,
            'otp'                => $this->otp,
        ], fn($v) => $v !== null);
    }
}
