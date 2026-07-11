<?php

namespace Shahkar\DataCenter\Exceptions;

class OtpRequiredException extends ShahkarApiException
{
    public function __construct(
        private readonly string $requestId,
        ?array $responseBody = null
    ) {
        parent::__construct(
            'OTP verification required. Please submit the request again with the OTP code.',
            $responseBody,
            202
        );
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
