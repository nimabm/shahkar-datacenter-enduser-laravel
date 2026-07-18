<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV1DTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonUpdateV1DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV1DTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

interface DataCenterApiV1Interface
{
    public function registerForNaturalPerson(
        NaturalPersonV1DTO   $person,
        AddressDTO         $address,
        ServiceDataInterface $service,
        ?string            $requestId
    ): ApiResponse;

    public function registerForLegalPerson(
        LegalPersonV1DTO     $person,
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
        ?LegalPersonUpdateV1DTO $customerUpdate,
        ?string             $requestId
    ): ApiResponse;

    public function close(
        string  $serviceId,
        ?string $requestId
    ): ApiResponse;
}
