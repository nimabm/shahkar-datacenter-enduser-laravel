<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Reseller\CustomerUpdateResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\LegalPersonResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\NaturalPersonResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\ResellerServiceDTO;
use Shahkar\DataCenter\DTOs\Reseller\ResellerServiceUpdateDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

/**
 * Shahkar "Reseller Code" service (کد نمایندهٔ فروش) — document v9.4, service type 30.
 *
 * A standalone service, independent of the Data Center web service, used to
 * register/update/transfer/close/delete a reseller's sales code. It is single-step
 * with no OTP. It shares the `rest/shahkar/{put,update,delete}` endpoints with other
 * Shahkar services; Shahkar distinguishes it by `service.type = 30`.
 *
 * Note on codes: the code being registered lives in {@see ResellerServiceDTO} and
 * becomes the service's `serviceNumber` for later operations. The top-level
 * `resellerCode` (the requesting operator's own code) is taken from
 * `config('shahkar-datacenter.reseller_code')`.
 */
interface ResellerApiInterface
{
    public function registerForNaturalPerson(
        NaturalPersonResellerDTO $person,
        ResellerServiceDTO       $service,
        ?AddressDTO              $address = null,
        ?string                 $requestId = null,
    ): ApiResponse;

    public function registerForLegalPerson(
        LegalPersonResellerDTO $person,
        ResellerServiceDTO     $service,
        ?AddressDTO            $address = null,
        ?string               $requestId = null,
    ): ApiResponse;

    /**
     * Update an existing reseller service. `$serviceNumber` is the reseller code
     * assigned at registration.
     */
    public function update(
        string                     $serviceId,
        string                     $serviceNumber,
        ?ResellerServiceUpdateDTO  $serviceUpdate = null,
        ?AddressUpdateDTO          $addressUpdate = null,
        ?CustomerUpdateResellerDTO $customerUpdate = null,
        ?string                    $requestId = null,
    ): ApiResponse;

    /**
     * Transfer the service to a new natural-person owner.
     */
    public function transferToNaturalPerson(
        string                   $serviceId,
        string                   $serviceNumber,
        NaturalPersonResellerDTO $person,
        ?AddressDTO              $address = null,
        ?string                 $requestId = null,
    ): ApiResponse;

    /**
     * Transfer the service to a new legal-person owner.
     */
    public function transferToLegalPerson(
        string                 $serviceId,
        string                 $serviceNumber,
        LegalPersonResellerDTO $person,
        ?AddressDTO            $address = null,
        ?string               $requestId = null,
    ): ApiResponse;

    /**
     * Close (terminate) the service. Sent to the update endpoint with "close": 1.
     */
    public function close(
        string  $serviceId,
        string  $serviceNumber,
        ?string $requestId = null,
    ): ApiResponse;

    /**
     * Permanently delete the service.
     */
    public function delete(
        string  $serviceId,
        string  $serviceNumber,
        ?string $requestId = null,
    ): ApiResponse;
}
