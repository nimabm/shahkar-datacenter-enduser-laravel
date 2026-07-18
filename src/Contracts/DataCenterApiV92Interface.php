<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\CustomerUpdateV92DTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV92DTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

/**
 * Contract for the Shahkar DC EndUser V9.2 flow.
 *
 * Unlike the current flow, V9.2 is single-step and has no OTP: register, update,
 * close and delete each complete in one request. Full personal details are sent
 * inline with the request instead of being resolved from an OTP challenge.
 */
interface DataCenterApiV92Interface
{
    /**
     * Register a data center service for a natural person (single request, no OTP).
     */
    public function registerForNaturalPerson(
        NaturalPersonV92DTO  $person,
        AddressDTO           $address,
        ServiceDataInterface $service,
        ?string              $requestId = null
    ): ApiResponse;

    /**
     * Register a data center service for a legal person (single request, no OTP).
     */
    public function registerForLegalPerson(
        LegalPersonV92DTO    $person,
        AddressDTO           $address,
        ServiceDataInterface $service,
        ?string              $requestId = null
    ): ApiResponse;

    /**
     * Update an existing service. The same call is used for natural and legal
     * persons; pass a $customerUpdate for the fields you want to change.
     */
    public function update(
        string                 $serviceId,
        string                 $serviceNumber,
        ServiceDataInterface   $serviceUpdate,
        ?AddressUpdateDTO      $addressUpdate = null,
        ?CustomerUpdateV92DTO  $customerUpdate = null,
        ?string                $requestId = null
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
