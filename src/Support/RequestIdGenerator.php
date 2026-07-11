<?php

namespace Shahkar\DataCenter\Support;

class RequestIdGenerator
{
    /**
     * Generate a unique request ID compatible with Shahkar API format.
     * Format: operatorId + timestamp + random suffix
     */
    public static function generate(string $operatorId = '013'): string
    {
        $timestamp = now()->format('YmdHis');
        $random    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return $operatorId . $timestamp . $random;
    }
}
