<?php

namespace Shahkar\DataCenter\DTOs\Person;

/**
 * Optional customer fields to change on a V9.2 update request.
 *
 * Sent as the "customerUpdate" object. Only non-null fields are included, so a
 * caller changes just what they need (e.g. email, mobile, companyName).
 *
 * @see "Shahkar DC EndUser V9.2" — update request samples
 */
class CustomerUpdateV92DTO
{
    public function __construct(
        public readonly ?string $mobile = null,
        public readonly ?string $email = null,
        public readonly ?string $name = null,
        public readonly ?string $family = null,
        public readonly ?string $fatherName = null,
        public readonly ?string $companyName = null,
        public readonly ?string $agentFirstName = null,
        public readonly ?string $agentLastName = null,
        public readonly ?string $agentFatherName = null,
        public readonly ?string $agentMobile = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'mobile'          => $this->mobile,
            'email'           => $this->email,
            'name'            => $this->name,
            'family'          => $this->family,
            'fatherName'      => $this->fatherName,
            'companyName'     => $this->companyName,
            'agentFirstName'  => $this->agentFirstName,
            'agentLastName'   => $this->agentLastName,
            'agentFatherName' => $this->agentFatherName,
            'agentMobile'     => $this->agentMobile,
        ], fn($v) => $v !== null);
    }
}
