<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

interface DataCenterApiInterface
{
    public function registerForNaturalPerson(
        NaturalPersonDTO   $person,
        AddressDTO         $address,
        ServiceDataInterface $service,
        ?string            $requestId
    ): ApiResponse;

    public function registerForLegalPerson(
        LegalPersonDTO     $person,
        AddressDTO         $address,
        ServiceDataInterface $service,
        ?string            $requestId
    ): ApiResponse;

    public function updateForNaturalPerson(
        string              $serviceId,
        string              $serviceNumber,
        int                 $otp,
        ServiceDataInterface $serviceUpdate,
        ?AddressUpdateDTO   $addressUpdate,
        ?string             $requestId
    ): ApiResponse;

    public function updateForLegalPerson(
        string              $serviceId,
        string              $serviceNumber,
        int                 $otp,
        int                 $agentOtp,
        ServiceDataInterface $serviceUpdate,
        ?AddressUpdateDTO   $addressUpdate,
        ?LegalPersonUpdateDTO $customerUpdate,
        ?string             $requestId
    ): ApiResponse;

    public function close(
        string  $serviceId,
        ?string $requestId
    ): ApiResponse;
}
