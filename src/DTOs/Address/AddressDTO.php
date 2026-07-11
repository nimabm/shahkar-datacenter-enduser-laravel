<?php

namespace Shahkar\DataCenter\DTOs\Address;

class AddressDTO
{
    public function __construct(
        public readonly string $provinceCode,
        public readonly string $address,
        public readonly string $houseNumber,
        public readonly string $postalCode,
        public readonly ?string $townshipName = null,
        public readonly ?string $street2 = null,
        public readonly ?string $tel = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'provinceCode' => $this->provinceCode,
            'townshipName' => $this->townshipName,
            'address'      => $this->address,
            'street2'      => $this->street2,
            'houseNumber'  => $this->houseNumber,
            'postalCode'   => $this->postalCode,
            'tel'          => $this->tel,
        ], fn($v) => $v !== null);
    }
}
