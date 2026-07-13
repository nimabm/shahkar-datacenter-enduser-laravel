<?php

namespace Shahkar\DataCenter\Support;

use DateTimeImmutable;
use DateTimeZone;

class RequestIdGenerator
{
    /**
     * Generate a unique request ID compatible with the NSCRA API format:
     *   providerCode + Tehran-local timestamp (YmdHis + microseconds)
     *
     * Mirrors generate_request_id() in sample_python/main.py.
     */
    public static function generate(string $providerCode): string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Tehran'));

        return $providerCode . $now->format('YmdHisu');
    }
}
