<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\CustomerUpdateV92DTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV92DTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

/**
 * Contract for the "Shahkar DC EndUser V9.4" flow.
 *
 * Same single-step, no-OTP flow and endpoints as V9.2. The difference is the
 * service data: the single `ips` field is replaced by `publicIps` (required)
 * and `privateIps` (optional), so only the *V94DTO service classes are
 * accepted. Person and customer-update fields are unchanged from V9.2, so the
 * V9.2 person DTOs are reused.
 */
interface DataCenterApiV94Interface
{
    /**
     * Register a data center service for a natural person (single request, no OTP).
     */
    public function registerForNaturalPerson(
        NaturalPersonV92DTO     $person,
        AddressDTO              $address,
        ServiceDataV94Interface $service,
        ?string                 $requestId = null
    ): ApiResponse;

    /**
     * Register a data center service for a legal person (single request, no OTP).
     */
    public function registerForLegalPerson(
        LegalPersonV92DTO       $person,
        AddressDTO              $address,
        ServiceDataV94Interface $service,
        ?string                 $requestId = null
    ): ApiResponse;

    /**
     * Update an existing service. The same call is used for natural and legal
     * persons; pass a $customerUpdate for the person fields you want to change.
     */
    public function update(
        string                  $serviceId,
        string                  $serviceNumber,
        ServiceDataV94Interface $serviceUpdate,
        ?AddressUpdateDTO       $addressUpdate = null,
        ?CustomerUpdateV92DTO   $customerUpdate = null,
        ?string                 $requestId = null
    ): ApiResponse;

    /**
     * Close (terminate) a service. Sent to the update endpoint with "close": 1.
     */
    public function close(
        string  $serviceId,
        string  $serviceNumber,
        ?string $requestId = null
    ): ApiResponse;

    /**
     * Permanently delete a service.
     */
    public function delete(
        string  $serviceId,
        string  $serviceNumber,
        ?string $requestId = null
    ): ApiResponse;
}
