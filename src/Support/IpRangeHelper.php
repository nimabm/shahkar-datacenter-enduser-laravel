<?php

namespace Shahkar\DataCenter\Support;

use InvalidArgumentException;

class IpRangeHelper
{
    /**
     * Formats an array of IP ranges into the comma-separated string expected by the API.
     * Each range can be: '1.2.3.4-1.2.3.10' or just '1.2.3.4' for a single IP.
     *
     * @param  array<string> $ranges
     */
    public static function format(array $ranges): string
    {
        return implode(',', array_map('trim', $ranges));
    }

    /**
     * Validates that each item is a valid IP or IP range and there are no overlaps.
     *
     * @param  array<string> $ranges
     * @throws InvalidArgumentException
     */
    public static function validate(array $ranges): void
    {
        $longRanges = [];

        foreach ($ranges as $range) {
            $parts = explode('-', trim($range));

            if (count($parts) === 1) {
                $start = $end = ip2long(trim($parts[0]));
            } elseif (count($parts) === 2) {
                $start = ip2long(trim($parts[0]));
                $end   = ip2long(trim($parts[1]));
            } else {
                throw new InvalidArgumentException("Invalid IP range format: {$range}");
            }

            if ($start === false || $end === false) {
                throw new InvalidArgumentException("Invalid IP address in range: {$range}");
            }

            if ($start > $end) {
                throw new InvalidArgumentException("Invalid IP range (start > end): {$range}");
            }

            foreach ($longRanges as [$existStart, $existEnd]) {
                if ($start <= $existEnd && $end >= $existStart) {
                    throw new InvalidArgumentException(
                        "IP range overlap detected for range: {$range}"
                    );
                }
            }

            $longRanges[] = [$start, $end];
        }
    }
}
