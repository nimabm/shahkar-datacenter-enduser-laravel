<?php

namespace Shahkar\DataCenter\DTOs\Person;

use Shahkar\DataCenter\Enums\IdentificationType;

class LegalPersonV1DTO
{
    public function __construct(
        public readonly string $identificationNo,
        public readonly string $mobileNumber,
        public readonly string $agentIdentificationNo,
        public readonly ?int $otp = null,
        public readonly ?int $agentOtp = null,
        public readonly IdentificationType $identificationType = IdentificationType::NationalId,
        public readonly IdentificationType $agentIdentificationType = IdentificationType::NationalCode,
        public readonly ?string $nationality = null,
        public readonly ?string $agentNationality = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'identificationType'      => $this->identificationType->value,
            'identificationNo'        => $this->identificationNo,
            'nationality'             => $this->nationality,
            'mobileNumber'            => $this->mobileNumber,
            'agentIdentificationType' => $this->agentIdentificationType->value,
            'agentIdentificationNo'   => $this->agentIdentificationNo,
            'agentNationality'        => $this->agentNationality,
            'otp'                     => $this->otp,
            'agentOtp'                => $this->agentOtp,
        ], fn($v) => $v !== null);
    }
}
