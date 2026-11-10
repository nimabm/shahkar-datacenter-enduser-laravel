<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV1DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV1DTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

/**
 * Contract for the "Shahkar DC EndUser V1.2" flow (new authentication, OTP).
 *
 * Same two-step OTP flow and endpoints as V1.0, with these changes:
 *  - service data uses `publicIps` (required) and `privateIps` (optional)
 *    instead of `ips`, so only the *V12DTO service classes are accepted;
 *  - update identifies the service by `id` only: no `serviceNumber`, no
 *    `dataCenterId` in `serviceUpdate`, and no `customerUpdate`;
 *  - update is two-step like registration: send it without OTPs first, then
 *    again with the same requestId and the OTP(s) filled in.
 *
 * Person fields are unchanged from V1.0, so the V1.0 person DTOs are reused.
 */
interface DataCenterApiV12Interface
{
    /**
     * Register for a natural person. Call twice: first with no `otp` on the
     * person DTO (Shahkar sends the code), then with the same requestId and
     * the `otp` filled in.
     */
    public function registerForNaturalPerson(
        NaturalPersonV1DTO      $person,
        AddressDTO              $address,
        ServiceDataV12Interface $service,
        ?string                 $requestId = null
    ): ApiResponse;

    /**
     * Register for a legal person. Call twice: first with no `otp`/`agentOtp`
     * on the person DTO (Shahkar sends both codes), then with the same
     * requestId and both codes filled in.
     */
    public function registerForLegalPerson(
        LegalPersonV1DTO        $person,
        AddressDTO              $address,
        ServiceDataV12Interface $service,
        ?string                 $requestId = null
    ): ApiResponse;

    /**
     * Update a natural person's service. Call twice: first with $otp null,
     * then with the same requestId and the OTP.
     */
    public function updateForNaturalPerson(
        string                  $serviceId,
        ServiceDataV12Interface $serviceUpdate,
        ?AddressUpdateDTO       $addressUpdate = null,
        ?int                    $otp = null,
        ?string                 $requestId = null
    ): ApiResponse;

    /**
     * Update a legal person's service. Call twice: first with $otp and
     * $agentOtp null, then with the same requestId and both codes.
     */
    public function updateForLegalPerson(
        string                  $serviceId,
        ServiceDataV12Interface $serviceUpdate,
        ?AddressUpdateDTO       $addressUpdate = null,
        ?int                    $otp = null,
        ?int                    $agentOtp = null,
        ?string                 $requestId = null
    ): ApiResponse;

    /**
     * Close (terminate) a service.
     */
    public function close(
        string  $serviceId,
        ?string $requestId = null
    ): ApiResponse;
}
