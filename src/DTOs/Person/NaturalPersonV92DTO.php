<?php

namespace Shahkar\DataCenter\DTOs\Person;

/**
 * Natural person for the V9.2 (single-step, no-OTP) flow.
 *
 * Because V9.2 has no OTP, the full identity is sent inline with the request.
 * The document maps identity fields as follows:
 *   - Iranian  : identificationType = 0, iranian = 1 (certificateNo applies)
 *   - Foreign  : identificationType = 1, iranian = 0 (nationality + universalNo apply)
 * `person` is always 1 for a natural person.
 *
 * @see "Shahkar DC EndUser V9.2" — sharedWebHosting / VPS / CDN request samples
 */
class NaturalPersonV92DTO
{
    public function __construct(
        public readonly string  $identificationNo,
        public readonly string  $name,
        public readonly string  $family,
        public readonly string  $mobile,
        public readonly bool    $iranian = true,
        public readonly ?string $fatherName = null,
        public readonly ?string $birthDate = null,
        public readonly ?string $birthPlace = null,
        public readonly ?string $certificateNo = null,
        public readonly ?int    $gender = null,
        public readonly ?string $email = null,
        public readonly ?string $nationality = null, // foreign persons
        public readonly ?string $universalNo = null, // foreign persons
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name'               => $this->name,
            'family'             => $this->family,
            'fatherName'         => $this->fatherName,
            'certificateNo'      => $this->certificateNo,
            'birthDate'          => $this->birthDate,
            'birthPlace'         => $this->birthPlace,
            'mobile'             => $this->mobile,
            'email'              => $this->email,
            'gender'             => $this->gender,
            'identificationNo'   => $this->identificationNo,
            'identificationType' => $this->iranian ? 0 : 1,
            'nationality'        => $this->nationality,
            'universalNo'        => $this->universalNo,
            'iranian'            => $this->iranian ? 1 : 0,
            'person'             => 1,
        ], fn($v) => $v !== null);
    }
}
