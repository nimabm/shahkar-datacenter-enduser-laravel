<?php

namespace Shahkar\DataCenter\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

class ShahkarHttpClient implements HttpClientInterface
{
    private Client $client;

    public function __construct(private readonly array $config)
    {
        $this->client = new Client([
            'base_uri'    => rtrim($config['base_url'], '/') . '/',
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
        ]);
    }

    public function post(string $endpoint, array $payload): ApiResponse
    {
        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body       = json_decode((string) $response->getBody(), true) ?? [];

            $this->handleErrorStatus($statusCode, $body);

            return new ApiResponse(
                success:    $statusCode >= 200 && $statusCode < 300,
                statusCode: $statusCode,
                body:       $body,
                requestId:  $payload['requestId'] ?? '',
            );
        } catch (ConnectException $e) {
            throw new ShahkarApiException(
                'Unable to connect to Shahkar API: ' . $e->getMessage(),
                null,
                0,
                $e
            );
        } catch (ShahkarApiException $e) {
            throw $e;
        }
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
