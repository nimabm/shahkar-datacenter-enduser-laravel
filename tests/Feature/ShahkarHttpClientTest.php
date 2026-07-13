<?php

namespace Shahkar\DataCenter\Tests\Feature;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;
use Shahkar\DataCenter\Http\ShahkarHttpClient;

class ShahkarHttpClientTest extends TestCase
{
    /** @var array<int,RequestInterface> */
    private array $history = [];

    private function makeClient(MockHandler $mock, array $overrides = []): ShahkarHttpClient
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->history));

        return new ShahkarHttpClient(array_merge([
            'base_url' => 'https://nscra.test/api/1.0/external',
            'api_key'  => 'secret-key',
            'handler'  => $stack,
        ], $overrides));
    }

    public function test_post_sends_api_key_header_and_wraps_response(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => ['trackingId' => 'TRK-1']])),
        ]);

        $response = $this->makeClient($mock)->post('/dc/send', ['signedEncryptedPayload' => 'abc']);

        $this->assertTrue($response->success);
        $this->assertSame('TRK-1', $response->getTrackingId());
        $this->assertSame('secret-key', $this->history[0]['request']->getHeaderLine('X-API-KEY'));
        $this->assertSame('POST', $this->history[0]['request']->getMethod());
    }

    public function test_get_hits_status_endpoint(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['data' => ['responseBody' => 'enc']])),
        ]);

        $response = $this->makeClient($mock)->get('/dc/status/TRK-1');

        $this->assertTrue($response->success);
        $this->assertStringEndsWith('/dc/status/TRK-1', (string) $this->history[0]['request']->getUri());
        $this->assertSame('GET', $this->history[0]['request']->getMethod());
    }

    public function test_422_throws_validation_exception(): void
    {
        $mock = new MockHandler([
            new Response(422, [], json_encode(['message' => 'Invalid data'])),
        ]);

        $this->expectException(ShahkarValidationException::class);
        $this->expectExceptionMessage('Invalid data');

        $this->makeClient($mock)->post('/dc/send', []);
    }

    public function test_4xx_throws_api_exception_with_status_code(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['message' => 'Bad request'])),
        ]);

        try {
            $this->makeClient($mock)->post('/dc/send', []);
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

        $this->makeClient($mock)->post('/dc/send', []);
    }

    public function test_connection_error_is_retried_then_succeeds(): void
    {
        $mock = new MockHandler([
            new ConnectException('timeout', new Request('POST', 'dc/send')),
            new Response(200, [], json_encode(['ok' => true])),
        ]);

        $client   = $this->makeClient($mock, ['retry' => ['times' => 2, 'sleep' => 0]]);
        $response = $client->post('/dc/send', []);

        $this->assertTrue($response->success);
        $this->assertSame(0, $mock->count());
    }

    public function test_connection_error_throws_after_exhausting_retries(): void
    {
        $mock = new MockHandler([
            new ConnectException('timeout', new Request('POST', 'dc/send')),
            new ConnectException('timeout', new Request('POST', 'dc/send')),
        ]);

        $client = $this->makeClient($mock, ['retry' => ['times' => 2, 'sleep' => 0]]);

        $this->expectException(ShahkarApiException::class);
        $this->expectExceptionMessage('Unable to connect to Shahkar API');

        $client->post('/dc/send', []);
    }
}
