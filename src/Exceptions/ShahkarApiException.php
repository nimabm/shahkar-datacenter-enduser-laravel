<?php

namespace Shahkar\DataCenter\Exceptions;

use RuntimeException;

class ShahkarApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?array $responseBody = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }
}
