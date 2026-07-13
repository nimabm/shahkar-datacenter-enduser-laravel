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
    /**
     * Register (or confirm, with $otp) the client public key with the server.
     * Call once without $otp to receive an OTP, then again with the OTP.
     */
    public function registerKey(string $otp = ''): ApiResponse;

    /**
     * Register a data center service for a natural person.
     * Two-step: call without OTP (in $person) first, then again with the OTP.
     */
    public function registerForNaturalPerson(
        NaturalPersonDTO     $person,
        AddressDTO           $address,
        ServiceDataInterface $service,
        ?string              $requestId = null
    ): ApiResponse;

    /**
     * Register a data center service for a legal person.
     * Two-step: call without OTPs first, then again with otp + agentOtp.
     */
    public function registerForLegalPerson(
        LegalPersonDTO       $person,
        AddressDTO           $address,
        ServiceDataInterface $service,
        ?string              $requestId = null
    ): ApiResponse;

    public function updateForNaturalPerson(
        string               $serviceId,
        string               $serviceNumber,
        ServiceDataInterface $serviceUpdate,
        ?int                 $otp = null,
        ?AddressUpdateDTO    $addressUpdate = null,
        ?string              $requestId = null
    ): ApiResponse;

    public function updateForLegalPerson(
        string                $serviceId,
        string                $serviceNumber,
        ServiceDataInterface  $serviceUpdate,
        ?int                  $otp = null,
        ?int                  $agentOtp = null,
        ?AddressUpdateDTO     $addressUpdate = null,
        ?LegalPersonUpdateDTO $customerUpdate = null,
        ?string               $requestId = null
    ): ApiResponse;

    /** Close (delete) an existing data center service. */
    public function delete(string $serviceId, ?string $requestId = null): ApiResponse;

    /** Alias of delete(). */
    public function close(string $serviceId, ?string $requestId = null): ApiResponse;

    /** Poll the async result of a previously accepted request by its tracking id. */
    public function checkStatus(string $trackingId): ApiResponse;
}
