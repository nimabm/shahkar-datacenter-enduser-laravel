<?php

namespace Shahkar\DataCenter\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\ResponseInterface;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

/**
 * Low-level HTTP transport for the NSCRA data-center API.
 *
 * Adds the X-API-KEY header, JSON encodes bodies, maps HTTP error statuses to
 * exceptions, and retries only on connection failures. The signing/encryption
 * of the payload is handled one layer up by the crypto service.
 */
class ShahkarHttpClient implements HttpClientInterface
{
    private Client $client;

    public function __construct(private readonly array $config)
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        if (! empty($config['api_key'])) {
            $headers['X-API-KEY'] = $config['api_key'];
        }

        $options = [
            'base_uri'    => rtrim($config['base_url'] ?? '', '/') . '/',
            'timeout'     => $config['timeout'] ?? 30,
            'verify'      => $config['verify_ssl'] ?? true,
            'http_errors' => false,
            'headers'     => $headers,
        ];

        // Allow a custom Guzzle handler to be injected (used in tests).
        if (isset($config['handler'])) {
            $options['handler'] = $config['handler'];
        }

        $this->client = new Client($options);
    }

    public function post(string $endpoint, array $body): ApiResponse
    {
        return $this->send('POST', ltrim($endpoint, '/'), ['json' => $body]);
    }

    public function get(string $endpoint): ApiResponse
    {
        return $this->send('GET', ltrim($endpoint, '/'), []);
    }

    private function send(string $method, string $endpoint, array $options): ApiResponse
    {
        $maxAttempts = max(1, (int) ($this->config['retry']['times'] ?? 1));
        $sleepMs     = (int) ($this->config['retry']['sleep'] ?? 0);

        // Only connection failures (the request never reached the server) are
        // retried. Requests that receive any HTTP response are never retried,
        // since register/update are non-idempotent (an OTP may already have
        // been sent to the subscriber).
        for ($attempt = 1; ; $attempt++) {
            try {
                $response = $this->client->request($method, $endpoint, $options);
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

        return $this->toApiResponse($response);
    }

    private function toApiResponse(ResponseInterface $response): ApiResponse
    {
        $statusCode = $response->getStatusCode();
        $body       = json_decode((string) $response->getBody(), true) ?? [];

        $this->handleErrorStatus($statusCode, $body);

        return new ApiResponse(
            success:    $statusCode >= 200 && $statusCode < 300,
            statusCode: $statusCode,
            body:       $body,
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
