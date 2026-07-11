<?php

namespace Shahkar\DataCenter\Exceptions;

class ShahkarValidationException extends ShahkarApiException
{
    public function __construct(string $message, ?array $responseBody = null)
    {
        parent::__construct($message, $responseBody, 422);
    }
}
