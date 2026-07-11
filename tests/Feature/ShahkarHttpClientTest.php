<?php

namespace Shahkar\DataCenter\Tests\Feature;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;
use Shahkar\DataCenter\Http\ShahkarHttpClient;

class ShahkarHttpClientTest extends TestCase
{
    private function makeClient(MockHandler $mock, array $overrides = []): ShahkarHttpClient
    {
        return new ShahkarHttpClient(array_merge([
            'base_url' => 'https://example.test',
            'handler'  => HandlerStack::create($mock),
        ], $overrides));
    }

    public function test_successful_response_is_wrapped(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['serviceNumber' => '12345'])),
        ]);

        $response = $this->makeClient($mock)->post('rest/x', ['requestId' => 'req-1']);

        $this->assertTrue($response->success);
        $this->assertSame(200, $response->statusCode);
        $this->assertSame('12345', $response->getServiceNumber());
        $this->assertSame('req-1', $response->requestId);
    }

    public function test_422_throws_validation_exception(): void
    {
        $mock = new MockHandler([
            new Response(422, [], json_encode(['message' => 'Invalid data'])),
        ]);

        $this->expectException(ShahkarValidationException::class);
        $this->expectExceptionMessage('Invalid data');

        $this->makeClient($mock)->post('rest/x', ['requestId' => 'req-1']);
    }

    public function test_4xx_throws_api_exception_with_status_code(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['message' => 'Bad request'])),
        ]);

        try {
            $this->makeClient($mock)->post('rest/x', []);
            $this->fail('Expected ShahkarApiException');
        } catch (ShahkarApiException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertSame('Bad request', $e->getMessage());
            $this->assertSame(['message' => 'Bad request'], $e->getResponseBody());
        }
    }

    public function test_5xx_throws_api_exception(): void
    {
        $mock = new MockHandler([
            new Response(500, [], json_encode(['message' => 'Boom'])),
        ]);

        $this->expectException(ShahkarApiException::class);

        $this->makeClient($mock)->post('rest/x', []);
    }

    public function test_connection_error_is_retried_then_succeeds(): void
    {
        $mock = new MockHandler([
            new ConnectException('timeout', new Request('POST', 'rest/x')),
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $client = $this->makeClient($mock, ['retry' => ['times' => 2, 'sleep' => 0]]);
        $response = $client->post('rest/x', ['requestId' => 'req-1']);

        $this->assertTrue($response->success);
        $this->assertSame(0, $mock->count()); // both queued responses consumed
    }

    public function test_connection_error_throws_after_exhausting_retries(): void
    {
        $mock = new MockHandler([
            new ConnectException('timeout', new Request('POST', 'rest/x')),
            new ConnectException('timeout', new Request('POST', 'rest/x')),
        ]);

        $client = $this->makeClient($mock, ['retry' => ['times' => 2, 'sleep' => 0]]);

        $this->expectException(ShahkarApiException::class);
        $this->expectExceptionMessage('Unable to connect to Shahkar API');

        $client->post('rest/x', []);
    }
}
