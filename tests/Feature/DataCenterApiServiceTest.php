<?php

namespace Shahkar\DataCenter\Tests\Feature;

use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\Crypto\NscraCryptoService;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Services\DataCenterApiService;
use Shahkar\DataCenter\Tests\Unit\NscraCryptoServiceTest;

/**
 * Fake HTTP client that records every outbound call so tests can inspect the
 * endpoint and decrypt the encrypted inner payload.
 */
class RecordingHttpClient implements HttpClientInterface
{
    /** @var array<int,array{method:string,endpoint:string,body:array}> */
    public array $sent = [];

    /** @var array<string,mixed> */
    public array $getBody = [];

    public function post(string $endpoint, array $body): ApiResponse
    {
        $this->sent[] = ['method' => 'POST', 'endpoint' => $endpoint, 'body' => $body];

        return new ApiResponse(true, 200, ['data' => ['trackingId' => 'TRK-1']]);
    }

    public function get(string $endpoint): ApiResponse
    {
        $this->sent[] = ['method' => 'GET', 'endpoint' => $endpoint, 'body' => []];

        return new ApiResponse(true, 200, $this->getBody);
    }
}

/**
 * Uses a real crypto service with throwaway keypairs and a recording HTTP
 * client, so the encrypted inner payload can be decrypted (server-side) and
 * asserted end to end.
 */
class DataCenterApiServiceTest extends TestCase
{
    private NscraCryptoService $serverCrypto;
    private RecordingHttpClient $http;
    private DataCenterApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        [$clientPriv, $clientPub] = NscraCryptoServiceTest::generateKeyPair();
        [$serverPriv, $serverPub] = NscraCryptoServiceTest::generateKeyPair();

        $clientCrypto       = new NscraCryptoService('client-1', $clientPriv, $clientPub, $serverPub);
        $this->serverCrypto = new NscraCryptoService('server', $serverPriv, $serverPub, $clientPub);

        $this->http    = new RecordingHttpClient();
        $this->service = new DataCenterApiService($this->http, $clientCrypto, 'PRV');
    }

    /** Decrypt the inner payload of the last captured POST envelope. */
    private function lastInnerData(): array
    {
        $last = end($this->http->sent);

        return $this->serverCrypto->decryptAndVerify($last['body']['signedEncryptedPayload']);
    }

    public function test_register_natural_person_encrypts_correct_payload(): void
    {
        $response = $this->service->registerForNaturalPerson(
            person:  new NaturalPersonDTO('0987654321'),
            address: new AddressDTO('021', 'Azadi Street', '10', '1234567890'),
            service: new SharedWebHostingServiceDTO('DC001', '1.2.3.4-1.2.3.4', 256, '14030101', 'cra.ir'),
        );

        $this->assertSame('/dc/send', $this->http->sent[0]['endpoint']);
        $this->assertArrayHasKey('signedEncryptedPayload', $this->http->sent[0]['body']);

        $data = $this->lastInnerData();
        $this->assertSame(0, $data['identificationType']);
        $this->assertSame('0987654321', $data['identificationNo']);
        $this->assertArrayNotHasKey('agentIdentificationNo', $data);
        $this->assertSame(14, $data['service']['dataCenterType']);
        $this->assertSame('021', $data['address']['provinceCode']);
        $this->assertStringStartsWith('PRV', $data['requestId']);

        $this->assertSame('TRK-1', $response->getTrackingId());
        $this->assertStringStartsWith('PRV', $response->requestId);
    }

    public function test_register_legal_person_includes_agent_fields(): void
    {
        $this->service->registerForLegalPerson(
            person:  new LegalPersonDTO('33273340437', '09128964532', '0072314567'),
            address: new AddressDTO('021', 'Azadi Street', '10', '1234567890'),
            service: new SharedWebHostingServiceDTO('DC001', '1.2.3.4-1.2.3.4', 256, '14030101', 'cra.ir'),
        );

        $data = $this->lastInnerData();
        $this->assertSame(5, $data['identificationType']);
        $this->assertSame('09128964532', $data['mobileNumber']);
        $this->assertSame(0, $data['agentIdentificationType']);
        $this->assertSame('0072314567', $data['agentIdentificationNo']);
    }

    public function test_update_natural_person_targets_update_endpoint(): void
    {
        $this->service->updateForNaturalPerson(
            serviceId:     'WZOzs3PX2rKT',
            serviceNumber: '34689658',
            serviceUpdate: new SharedWebHostingUpdateDTO('DC001', ips: '1.2.3.5-1.2.3.5'),
            otp:           12345,
        );

        $this->assertSame('/dc/update', $this->http->sent[0]['endpoint']);

        $data = $this->lastInnerData();
        $this->assertSame('WZOzs3PX2rKT', $data['id']);
        $this->assertSame('34689658', $data['serviceNumber']);
        $this->assertSame(12345, $data['otp']);
        $this->assertSame('1.2.3.5-1.2.3.5', $data['serviceUpdate']['ips']);
    }

    public function test_update_omits_otp_on_first_step(): void
    {
        $this->service->updateForNaturalPerson(
            serviceId:     'svc',
            serviceNumber: '111',
            serviceUpdate: new SharedWebHostingUpdateDTO('DC001', ips: '1.2.3.5-1.2.3.5'),
        );

        $data = $this->lastInnerData();
        $this->assertArrayNotHasKey('otp', $data);
    }

    public function test_delete_and_close_target_delete_endpoint(): void
    {
        $this->service->close('svc-id');

        $this->assertSame('/dc/delete', $this->http->sent[0]['endpoint']);
        $data = $this->lastInnerData();
        $this->assertSame('svc-id', $data['id']);
        $this->assertStringStartsWith('PRV', $data['requestId']);
        $this->assertCount(2, $data); // only requestId + id
    }

    public function test_register_key_posts_plaintext_credentials(): void
    {
        $this->service->registerKey('9988');

        $this->assertSame('/dc/register-key', $this->http->sent[0]['endpoint']);
        $body = $this->http->sent[0]['body'];
        $this->assertSame('client-1', $body['clientId']);
        $this->assertSame('9988', $body['otp']);
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $body['publicKey']);
        $this->assertArrayNotHasKey('signedEncryptedPayload', $body);
    }

    public function test_check_status_calls_status_endpoint_and_returns_decrypted(): void
    {
        // "peer" acts as the server encrypting a response for our client key.
        [$cliPriv, $cliPub]   = NscraCryptoServiceTest::generateKeyPair();
        [$peerPriv, $peerPub] = NscraCryptoServiceTest::generateKeyPair();

        $clientCrypto = new NscraCryptoService('client', $cliPriv, $cliPub, $peerPub);
        $peerCrypto   = new NscraCryptoService('peer', $peerPriv, $peerPub, $cliPub);

        $http = new RecordingHttpClient();
        $http->getBody = [
            'data' => [
                'responseBody' => $peerCrypto->encryptAndSign(
                    ['serviceNumber' => '34689658', 'message' => 'OK'],
                    '/response'
                ),
            ],
        ];

        $service  = new DataCenterApiService($http, $clientCrypto, 'PRV');
        $response = $service->checkStatus('TRK-99');

        $this->assertSame('/dc/status/TRK-99', $http->sent[0]['endpoint']);
        $this->assertNotNull($response->getDecrypted());
        $this->assertSame('34689658', $response->getServiceNumber());
        $this->assertSame('OK', $response->getMessage());
    }
}
