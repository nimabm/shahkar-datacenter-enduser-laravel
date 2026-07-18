<?php

namespace Shahkar\DataCenter\DTOs\Inquiry;

use Shahkar\DataCenter\Enums\InquiryIdentificationType;

/**
 * Natural person to verify via the Estelaam (identity inquiry) service.
 *
 * Iranian  : identificationType = NationalCode (0); birthDate is Jalali; certificateNo
 *            is required by Shahkar (send "0" when the person has none).
 * Foreign  : identificationType is one of Passport(1)/AmayeshCard(2)/RefugeeCard(3)/
 *            IdentityCard(4); birthDate is Gregorian; nationality is required;
 *            universalNo is optional.
 *
 * @see "Shahkar Estelaam API V1.4"
 */
class NaturalPersonInquiryDTO
{
    public function __construct(
        public readonly string $identificationNo,
        public readonly string $name,
        public readonly string $family,
        public readonly string $birthDate,
        public readonly InquiryIdentificationType $identificationType = InquiryIdentificationType::NationalCode,
        public readonly ?string $fatherName = null,
        public readonly ?string $certificateNo = null, // Iranian: "0" when none
        public readonly ?int    $gender = null,        // 1 = male, 2 = female
        public readonly ?string $nationality = null,   // foreign persons
        public readonly ?string $universalNo = null,   // foreign persons
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name'               => $this->name,
            'family'             => $this->family,
            'fatherName'         => $this->fatherName,
            'identificationType' => $this->identificationType->value,
            'birthDate'          => $this->birthDate,
            'identificationNo'   => $this->identificationNo,
            'certificateNo'      => $this->certificateNo,
            'nationality'        => $this->nationality,
            'universalNo'        => $this->universalNo,
            'gender'             => $this->gender,
        ], fn($v) => $v !== null);
    }
}
