<?php

namespace Shahkar\DataCenter\DTOs\Reseller;

/**
 * Natural person for the Reseller Code service (put / transfer) — document v9.4.
 *
 * Iranian : identificationType = 0, iranian = 1 (certificateNo applies).
 * Foreign : identificationType = 1, iranian = 0 (nationality + universalNo apply).
 * `person` is always 1 for a natural person.
 *
 * @see "Shahkar Reseller Code API V9.4"
 */
class NaturalPersonResellerDTO
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
        public readonly ?string $assertion = null,
        public readonly ?string $nationality = null, // foreign persons
        public readonly ?string $universalNo = null, // foreign persons
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'identificationType' => $this->iranian ? 0 : 1,
            'identificationNo'   => $this->identificationNo,
            'certificateNo'      => $this->certificateNo,
            'birthDate'          => $this->birthDate,
            'name'               => $this->name,
            'family'             => $this->family,
            'fatherName'         => $this->fatherName,
            'gender'             => $this->gender,
            'birthPlace'         => $this->birthPlace,
            'mobile'             => $this->mobile,
            'email'              => $this->email,
            'nationality'        => $this->nationality,
            'universalNo'        => $this->universalNo,
            'assertion'          => $this->assertion,
            'person'             => 1,
            'iranian'            => $this->iranian ? 1 : 0,
        ], fn($v) => $v !== null);
    }
}
