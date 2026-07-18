<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Inquiry\LegalPersonInquiryDTO;
use Shahkar\DataCenter\DTOs\Inquiry\NaturalPersonInquiryDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

/**
 * Shahkar "Estelaam" identity-inquiry service — document v1.4.
 *
 * A standalone service, independent of the Data Center web service: it verifies
 * a person's identity against Shahkar's reference registry (optionally alongside
 * their address). A single endpoint (`rest/shahkar/estelaam`) serves all person
 * types; there is no OTP.
 *
 * The verification outcome is carried in the response body (`response` code and
 * `result`), not by the HTTP status — e.g. response 200 / "OK." when verified,
 * or 610 / "CustomerNotFoundException" when not found. Inspect
 * `$response->get('response')` / `$response->get('result')`, not `$response->success`.
 */
interface InquiryApiInterface
{
    /**
     * Verify a natural person (Iranian or foreign).
     *
     * @param int|null $serviceType Optional service type for the compliance reference.
     */
    public function verifyNaturalPerson(
        NaturalPersonInquiryDTO $person,
        ?AddressDTO             $address = null,
        ?int                    $serviceType = null,
        ?string                 $requestId = null,
    ): ApiResponse;

    /**
     * Verify a legal person (Iranian or foreign).
     *
     * @param int|null $serviceType Optional service type for the compliance reference.
     */
    public function verifyLegalPerson(
        LegalPersonInquiryDTO $person,
        ?AddressDTO           $address = null,
        ?int                  $serviceType = null,
        ?string               $requestId = null,
    ): ApiResponse;
}
