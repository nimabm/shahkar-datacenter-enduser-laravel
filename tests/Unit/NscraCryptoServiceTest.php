<?php

namespace Shahkar\DataCenter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Crypto\NscraCryptoService;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;

class NscraCryptoServiceTest extends TestCase
{
    /** @return array{0:string,1:string} [privatePem, publicPem] */
    public static function generateKeyPair(): array
    {
        $res = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'prime256v1',
        ]);
        openssl_pkey_export($res, $private);
        $public = openssl_pkey_get_details($res)['key'];

        return [$private, $public];
    }

    public function test_sign_encrypt_round_trips_through_server(): void
    {
        [$clientPriv, $clientPub] = self::generateKeyPair();
        [$serverPriv, $serverPub] = self::generateKeyPair();

        $client = new NscraCryptoService('client-123', $clientPriv, $clientPub, $serverPub);
        $server = new NscraCryptoService('server', $serverPriv, $serverPub, $clientPub);

        $data = [
            'requestId'          => '123420250101000000000001',
            'identificationType' => 0,
            'identificationNo'   => '0987654321',
            'address'            => ['provinceCode' => '021', 'address' => 'Azadi Street'],
        ];

        $envelope  = $client->encryptAndSign($data, '/rest/shahkar/datacenter/put');
        $recovered = $server->decryptAndVerify($envelope);

        $this->assertSame($data, $recovered);
    }

    public function test_preserves_multibyte_utf8_payload(): void
    {
        [$clientPriv, $clientPub] = self::generateKeyPair();
        [$serverPriv, $serverPub] = self::generateKeyPair();

        $client = new NscraCryptoService('c', $clientPriv, $clientPub, $serverPub);
        $server = new NscraCryptoService('s', $serverPriv, $serverPub, $clientPub);

        // Non-ASCII UTF-8 (the API carries Persian address text); exercises the
        // JSON \uXXXX escaping used by the JWS payload without depending on any
        // particular script.
        $text      = 'Straße café — Москва «test»';
        $recovered = $server->decryptAndVerify($client->encryptAndSign(['address' => $text], '/x'));

        $this->assertSame($text, $recovered['address']);
    }

    public function test_verification_fails_with_wrong_server_key(): void
    {
        [$clientPriv, $clientPub]   = self::generateKeyPair();
        [$serverPriv, $serverPub]   = self::generateKeyPair();
        [, $strangerPub]            = self::generateKeyPair();

        // Client encrypts for the real server.
        $client = new NscraCryptoService('c', $clientPriv, $clientPub, $serverPub);
        $envelope = $client->encryptAndSign(['a' => 1], '/x');

        // Reader has the server private key but trusts a different signer.
        $reader = new NscraCryptoService('s', $serverPriv, $serverPub, $strangerPub);

        $this->expectException(ShahkarApiException::class);
        $reader->decryptAndVerify($envelope);
    }

    public function test_exposes_client_id_and_public_key(): void
    {
        [$clientPriv, $clientPub] = self::generateKeyPair();
        [, $serverPub]            = self::generateKeyPair();

        $client = new NscraCryptoService('client-xyz', $clientPriv, $clientPub, $serverPub);

        $this->assertSame('client-xyz', $client->getClientId());
        $this->assertStringContainsString('BEGIN PUBLIC KEY', $client->getClientPublicKey());
    }
}
