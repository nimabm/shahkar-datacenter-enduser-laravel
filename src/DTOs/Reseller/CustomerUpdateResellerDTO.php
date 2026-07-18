<?php

namespace Shahkar\DataCenter\DTOs\Reseller;

/**
 * Optional customer fields to change on a Reseller Code update request, sent as
 * the `customerUpdate` block. Only non-null fields are included.
 *
 * @see "Shahkar Reseller Code API V9.4"
 */
class CustomerUpdateResellerDTO
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
