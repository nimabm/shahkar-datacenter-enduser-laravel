<?php

namespace Shahkar\DataCenter\Support;

use InvalidArgumentException;

class PemLoader
{
    /**
     * Resolve a configuration value that may be either raw PEM content or a
     * path to a PEM file, returning the PEM content.
     */
    public static function load(string $value, string $label = 'key'): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException("Shahkar {$label} is not configured.");
        }

        if (str_contains($value, '-----BEGIN')) {
            return $value;
        }

        if (is_file($value)) {
            $contents = file_get_contents($value);

            if ($contents === false) {
                throw new InvalidArgumentException("Unable to read Shahkar {$label} file: {$value}");
            }

            return trim($contents);
        }

        throw new InvalidArgumentException(
            "Shahkar {$label} must be PEM content or a readable file path."
        );
    }
}
