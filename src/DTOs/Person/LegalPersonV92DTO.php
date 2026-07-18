<?php

namespace Shahkar\DataCenter\DTOs\Person;

/**
 * Legal person for the V9.2 (single-step, no-OTP) flow.
 *
 * The document maps identity fields as follows:
 *   - Iranian company : identificationType = 5, iranian = 1, agent type = 0
 *                       (agentBirthCertificateNo applies)
 *   - Foreign company : identificationType = 6, iranian = 0, agent type = 1
 *                       (nationality + agentNationality apply)
 * `person` is always 0 for a legal person.
 *
 * @see "Shahkar DC EndUser V9.2" — sharedWebHosting / VPS / CDN request samples
 */
class LegalPersonV92DTO
{
    public function __construct(
        public readonly string  $identificationNo,
        public readonly string  $mobile,
        public readonly string  $companyName,
        public readonly string  $agentIdentificationNo,
        public readonly bool    $iranian = true,
        public readonly ?string $agentFirstName = null,
        public readonly ?string $agentLastName = null,
        public readonly ?string $agentFatherName = null,
        public readonly ?string $agentMobile = null,
        public readonly ?string $agentBirthDate = null,
        public readonly ?string $agentBirthCertificateNo = null, // Iranian agent
        public readonly ?string $registrationNo = null,
        public readonly ?int    $companyType = null,
        public readonly ?string $registrationDate = null,
        public readonly ?string $email = null,
        public readonly ?string $nationality = null,      // foreign company
        public readonly ?string $agentNationality = null, // foreign agent
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'mobile'                  => $this->mobile,
            'identificationNo'        => $this->identificationNo,
            'identificationType'      => $this->iranian ? 5 : 6,
            'nationality'             => $this->nationality,
            'companyName'             => $this->companyName,
            'registrationNo'          => $this->registrationNo,
            'companyType'             => $this->companyType,
            'registrationDate'        => $this->registrationDate,
            'email'                   => $this->email,
            'agentFirstName'          => $this->agentFirstName,
            'agentLastName'           => $this->agentLastName,
            'agentFatherName'         => $this->agentFatherName,
            'agentIdentificationNo'   => $this->agentIdentificationNo,
            'agentIdentificationType' => $this->iranian ? 0 : 1,
            'agentNationality'        => $this->agentNationality,
            'agentBirthDate'          => $this->agentBirthDate,
            'agentBirthCertificateNo' => $this->agentBirthCertificateNo,
            'agentMobile'             => $this->agentMobile,
            'iranian'                 => $this->iranian ? 1 : 0,
            'person'                  => 0,
        ], fn($v) => $v !== null);
    }
}
