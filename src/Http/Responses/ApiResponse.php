<?php

namespace Shahkar\DataCenter\Http\Responses;

class ApiResponse
{
    public function __construct(
        public readonly bool   $success,
        public readonly int    $statusCode,
        public readonly array  $body,
        public readonly ?string $requestId = null,
        public readonly ?array $decrypted = null,
    ) {}

    /**
     * The tracking id returned by the queue when a request is accepted.
     * Use it with checkStatus() to poll for the final result.
     */
    public function getTrackingId(): ?string
    {
        return $this->body['trackingId']
            ?? $this->body['data']['trackingId']
            ?? $this->body['data']['id']
            ?? $this->body['id']
            ?? null;
    }

    public function getMessage(): ?string
    {
        return $this->decrypted['message']
            ?? $this->body['message']
            ?? $this->body['description']
            ?? null;
    }

    /**
     * The service number, read from the decrypted status result if present,
     * otherwise from the raw body.
     */
    public function getServiceNumber(): ?string
    {
        return $this->decrypted['serviceNumber']
            ?? $this->decrypted['data']['serviceNumber']
            ?? $this->body['serviceNumber']
            ?? $this->body['data']['serviceNumber']
            ?? null;
    }

    /** The decrypted+verified response body (set by checkStatus()). */
    public function getDecrypted(): ?array
    {
        return $this->decrypted;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function withRequestId(string $requestId): self
    {
        return new self($this->success, $this->statusCode, $this->body, $requestId, $this->decrypted);
    }

    public function withDecrypted(array $decrypted): self
    {
        return new self($this->success, $this->statusCode, $this->body, $this->requestId, $decrypted);
    }

    public function toArray(): array
    {
        return [
            'success'    => $this->success,
            'statusCode' => $this->statusCode,
            'requestId'  => $this->requestId,
            'trackingId' => $this->getTrackingId(),
            'body'       => $this->body,
            'decrypted'  => $this->decrypted,
        ];
    }
}
