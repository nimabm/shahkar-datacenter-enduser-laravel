<?php

namespace Shahkar\DataCenter\DTOs\Inquiry;

use Shahkar\DataCenter\Enums\InquiryIdentificationType;

/**
 * Legal person to verify via the Estelaam (identity inquiry) service.
 *
 * Iranian company : identificationType = NationalId (5).
 * Foreign company : identificationType = FidaId (6).
 * Unlike the Data Center flow, no agent information is sent.
 *
 * @see "Shahkar Estelaam API V1.4"
 */
class LegalPersonInquiryDTO
{
    public function __construct(
        public readonly string $identificationNo,
        public readonly string $companyName,
        public readonly int    $companyType,
        public readonly string $registrationDate,
        public readonly string $registrationNo,
        public readonly InquiryIdentificationType $identificationType = InquiryIdentificationType::NationalId,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'companyName'        => $this->companyName,
            'companyType'        => $this->companyType,
            'identificationType' => $this->identificationType->value,
            'identificationNo'   => $this->identificationNo,
            'registrationDate'   => $this->registrationDate,
            'registrationNo'     => $this->registrationNo,
        ], fn($v) => $v !== null);
    }
}
