<?php

namespace Shahkar\DataCenter\Enums;

/**
 * Supported Shahkar "End-User Data Center" API document versions.
 *
 * Each case is keyed by the version number printed on its own API document so
 * that the document itself stays the single source of truth. A developer picks
 * the flow they need by this key, e.g. ShahkarDataCenter::version('9.2').
 *
 * When a new document is published, add a case here keyed by its version number
 * and register an implementation for it in the service provider.
 */
enum ApiVersion: string
{
    /**
     * The current web service — a fresh v1.0 spec — using the two-step,
     * OTP-based flow. Registration is confirmed with an OTP challenge.
     */
    case V1_0 = '1.0';

    /**
     * "Shahkar DC EndUser V9.2" — the older single-step flow with no OTP.
     * Registration/update/close/delete each complete in a single request.
     */
    case V9_2 = '9.2';

    /**
     * Human-readable pointer to the document this version implements.
     */
    public function docReference(): string
    {
        return match ($this) {
            self::V1_0 => 'New web service v1.0 — two-step OTP (see README.md)',
            self::V9_2 => 'Shahkar DC EndUser V9.2 — single-step, no OTP',
        };
    }
}
