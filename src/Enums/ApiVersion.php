<?php

namespace Shahkar\DataCenter\Enums;

/**
 * Supported Shahkar "End-User Data Center" API document versions.
 *
 * Each case is keyed by the version number printed on its own API document so
 * that the document itself stays the single source of truth. A developer picks
 * the flow they need by this key, e.g. ShahkarDataCenter::version('9.4').
 *
 * When a new document is published, add a case here keyed by its version number
 * and register an implementation for it in the service provider.
 */
enum ApiVersion: string
{
    /**
     * "Shahkar DC EndUser new auth V1.0" — the two-step, OTP-based flow.
     * Registration is confirmed with an OTP challenge. Uses a single `ips` field.
     */
    case V1_0 = '1.0';

    /**
     * "Shahkar DC EndUser V1.2" — same two-step OTP flow as V1.0, with IPs split
     * into `publicIps` / `privateIps`, and update identified by `id` only.
     */
    case V1_2 = '1.2';

    /**
     * "Shahkar DC EndUser V9.2" — the older single-step flow with no OTP.
     * Registration/update/close/delete each complete in a single request.
     * Uses a single `ips` field.
     */
    case V9_2 = '9.2';

    /**
     * "Shahkar DC EndUser V9.4" — same single-step flow as V9.2, with IPs split
     * into `publicIps` / `privateIps`.
     */
    case V9_4 = '9.4';

    /**
     * Human-readable pointer to the document this version implements.
     */
    public function docReference(): string
    {
        return match ($this) {
            self::V1_0 => 'Shahkar DC EndUser new auth V1.0 — two-step OTP, single ips field',
            self::V1_2 => 'Shahkar DC EndUser V1.2 — two-step OTP, publicIps/privateIps',
            self::V9_2 => 'Shahkar DC EndUser V9.2 — single-step, no OTP, single ips field',
            self::V9_4 => 'Shahkar DC EndUser V9.4 — single-step, no OTP, publicIps/privateIps',
        };
    }
}
