<?php

namespace Shahkar\DataCenter\Http\Responses;

class ApiResponse
{
    public function __construct(
        public readonly bool   $success,
        public readonly int    $statusCode,
        public readonly array  $body,
        public readonly string $requestId,
    ) {}

    public function isOtpRequired(): bool
    {
        // When the first request is submitted, the API sends OTP and
        // typically returns a specific status or message
        return isset($this->body['otpSent']) && $this->body['otpSent'] === true;
    }

    public function getMessage(): ?string
    {
        return $this->body['message'] ?? $this->body['description'] ?? null;
    }

    public function getServiceNumber(): ?string
    {
        return $this->body['serviceNumber'] ?? $this->body['data']['serviceNumber'] ?? null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'success'    => $this->success,
            'statusCode' => $this->statusCode,
            'requestId'  => $this->requestId,
            'body'       => $this->body,
        ];
    }
}
