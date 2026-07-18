<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\Http\Responses\ApiResponse;

/**
 * Shahkar "IP Registration" ("معرفی IP", putIP) service — document v1.5.
 *
 * This is a standalone service, independent of the Data Center web service:
 * operators declare the IP ranges they advertise so that Data Center / end-user
 * IPs can be validated against them. It has no persons, addresses or OTP — just
 * lists of IP ranges keyed by a requestId.
 *
 * Each IP list is a comma-joined string of "start-end" ranges (a single IP is
 * written as "ip-ip", i.e. start == end). Array inputs are formatted for you via
 * {@see \Shahkar\DataCenter\Support\IpRangeHelper}.
 */
interface IpRegistrationApiInterface
{
    /**
     * Register (put) the full set of IP ranges this operator advertises.
     * This replaces the operator's previously registered list.
     *
     * @param string|array<string> $endUsersIPs       IPs assigned to end users
     * @param string|array<string> $dataCentersIPs    IPs assigned to data centers
     * @param string|array<string> $otherOperatorsIPs IPs assigned to other operators
     */
    public function put(
        string|array $endUsersIPs,
        string|array $dataCentersIPs,
        string|array $otherOperatorsIPs,
        ?string      $requestId = null,
    ): ApiResponse;

    /**
     * Delete ALL IPs currently registered for this operator.
     */
    public function truncate(?string $requestId = null): ApiResponse;

    /**
     * Fetch all IPs currently registered for this operator. The response body
     * carries `endUsersIPs`, `dataCentersIPs` and `otherOperatorsIPs`.
     */
    public function fetch(?string $requestId = null): ApiResponse;
}
