<?php

namespace Shahkar\DataCenter\DTOs\Person;

use Shahkar\DataCenter\Enums\IdentificationType;

class LegalPersonUpdateDTO
{
    public function __construct(
        public readonly string $agentIdentificationNo,
        public readonly IdentificationType $agentIdentificationType = IdentificationType::NationalCode,
    ) {}

    public function toArray(): array
    {
        return [
            'agentIdentificationType' => $this->agentIdentificationType->value,
            'agentIdentificationNo'   => $this->agentIdentificationNo,
        ];
    }
}
