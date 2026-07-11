<?php

namespace Shahkar\DataCenter\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

class ShahkarHttpClient implements HttpClientInterface
{
    private Client $client;

    public function __construct(private readonly array $config)
    {
        $options = [
            'base_uri'    => rtrim($config['base_url'] ?? '', '/') . '/',
            'timeout'     => $config['timeout'] ?? 30,
            'verify'      => $config['verify_ssl'] ?? true,
            'http_errors' => false,
            'headers'     => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'auth' => [
                $config['username'] ?? '',
                $config['password'] ?? '',
            ],
        ];

        // Allow a custom Guzzle handler to be injected (used in tests).
        if (isset($config['handler'])) {
            $options['handler'] = $config['handler'];
        }

        $this->client = new Client($options);
    }

    public function post(string $endpoint, array $payload): ApiResponse
    {
        $maxAttempts = max(1, (int) ($this->config['retry']['times'] ?? 1));
        $sleepMs     = (int) ($this->config['retry']['sleep'] ?? 0);

        // Only connection failures (the request never reached the server) are
        // retried. Requests that receive any HTTP response are never retried,
        // since registration/update are non-idempotent (an OTP may already have
        // been sent to the subscriber).
        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $this->client->post($endpoint, ['json' => $payload]);
                break;
            } catch (ConnectException $e) {
                if ($attempt >= $maxAttempts) {
                    throw new ShahkarApiException(
                        'Unable to connect to Shahkar API: ' . $e->getMessage(),
                        null,
                        0,
                        $e
                    );
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }
        }

        $statusCode = $response->getStatusCode();
        $body       = json_decode((string) $response->getBody(), true) ?? [];

        $this->handleErrorStatus($statusCode, $body);

        return new ApiResponse(
            success:    $statusCode >= 200 && $statusCode < 300,
            statusCode: $statusCode,
            body:       $body,
            requestId:  $payload['requestId'] ?? '',
        );
    }

    private function handleErrorStatus(int $statusCode, array $body): void
    {
        match (true) {
            $statusCode === 422 => throw new ShahkarValidationException(
                $body['message'] ?? 'Validation error',
                $body
            ),
            $statusCode >= 400 && $statusCode < 500 => throw new ShahkarApiException(
                $body['message'] ?? "Client error: HTTP {$statusCode}",
                $body,
                $statusCode
            ),
            $statusCode >= 500 => throw new ShahkarApiException(
                $body['message'] ?? "Server error: HTTP {$statusCode}",
                $body,
                $statusCode
            ),
            default => null,
        };
    }
}
