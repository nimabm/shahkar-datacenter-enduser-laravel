<?php

namespace Shahkar\DataCenter\DTOs\Address;

class AddressUpdateDTO
{
    public function __construct(
        public readonly ?string $townshipName = null,
        public readonly ?string $address = null,
        public readonly ?string $street2 = null,
        public readonly ?string $houseNumber = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $tel = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'townshipName' => $this->townshipName,
            'address'      => $this->address,
            'street2'      => $this->street2,
            'houseNumber'  => $this->houseNumber,
            'postalCode'   => $this->postalCode,
            'tel'          => $this->tel,
        ], fn($v) => $v !== null);
    }
}
