<?php

namespace Shahkar\DataCenter\DTOs\Reseller;

/**
 * Legal person for the Reseller Code service (put / transfer) — document v9.4.
 *
 * Iranian company : identificationType = 5, iranian = 1.
 * Foreign company : identificationType = 6, iranian = 0 (nationality applies).
 * The agent's identity type is independent of the company's: agentIranian toggles
 * agentIdentificationType between 0 (Iranian) and 1 (foreign).
 * `person` is always 0 for a legal person.
 *
 * @see "Shahkar Reseller Code API V9.4"
 */
class LegalPersonResellerDTO
{
    public function __construct(
        public readonly string  $identificationNo,
        public readonly string  $mobile,
        public readonly string  $companyName,
        public readonly string  $agentIdentificationNo,
        public readonly bool    $iranian = true,       // company
        public readonly bool    $agentIranian = true,  // agent
        public readonly ?int    $companyType = null,
        public readonly ?string $registrationDate = null,
        public readonly ?string $registrationNo = null,
        public readonly ?string $email = null,
        public readonly ?string $nationality = null,   // foreign company
        public readonly ?string $agentFirstName = null,
        public readonly ?string $agentLastName = null,
        public readonly ?string $agentFatherName = null,
        public readonly ?string $agentNationality = null,
        public readonly ?string $agentBirthDate = null,
        public readonly ?string $agentBirthCertificateNo = null,
        public readonly ?string $agentMobile = null,
        public readonly ?int    $agentGender = null,
        public readonly ?string $agentAssertion = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'person'                  => 0,
            'companyName'             => $this->companyName,
            'iranian'                 => $this->iranian ? 1 : 0,
            'identificationType'      => $this->iranian ? 5 : 6,
            'identificationNo'        => $this->identificationNo,
            'nationality'             => $this->nationality,
            'registrationDate'        => $this->registrationDate,
            'companyType'             => $this->companyType,
            'registrationNo'          => $this->registrationNo,
            'mobile'                  => $this->mobile,
            'email'                   => $this->email,
            'agentFirstName'          => $this->agentFirstName,
            'agentLastName'           => $this->agentLastName,
            'agentFatherName'         => $this->agentFatherName,
            'agentIdentificationType' => $this->agentIranian ? 0 : 1,
            'agentIdentificationNo'   => $this->agentIdentificationNo,
            'agentNationality'        => $this->agentNationality,
            'agentBirthDate'          => $this->agentBirthDate,
            'agentBirthCertificateNo' => $this->agentBirthCertificateNo,
            'agentMobile'             => $this->agentMobile,
            'agentGender'             => $this->agentGender,
            'agentAssertion'          => $this->agentAssertion,
        ], fn($v) => $v !== null);
    }
}
