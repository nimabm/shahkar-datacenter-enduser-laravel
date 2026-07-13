<?php

namespace Shahkar\DataCenter\Crypto;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Encryption\Algorithm\ContentEncryption\A256GCM;
use Jose\Component\Encryption\Algorithm\KeyEncryption\ECDHESA256KW;
use Jose\Component\Encryption\JWEBuilder;
use Jose\Component\Encryption\JWEDecrypter;
use Jose\Component\Encryption\Serializer\CompactSerializer as JweCompactSerializer;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer as JwsCompactSerializer;
use Shahkar\DataCenter\Contracts\CryptoServiceInterface;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Support\PemLoader;

/**
 * Implements the NSCRA request/response envelope:
 *   request:  data -> JWS (ES256, signed with client key) -> JWE (ECDH-ES+A256KW/A256GCM, encrypted for server)
 *   response: JWE (encrypted for client) -> JWS (signed by server) -> data
 *
 * Mirrors the reference implementation in sample_python/main.py.
 */
class NscraCryptoService implements CryptoServiceInterface
{
    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES;

    private JWK $clientKey;
    private JWK $serverKey;
    private string $clientPublicKeyPem;

    private JWSBuilder $jwsBuilder;
    private JWSVerifier $jwsVerifier;
    private JWEBuilder $jweBuilder;
    private JWEDecrypter $jweDecrypter;
    private JwsCompactSerializer $jwsSerializer;
    private JweCompactSerializer $jweSerializer;

    public function __construct(
        private readonly string $clientId,
        string $clientPrivateKeyPem,
        string $clientPublicKeyPem,
        string $serverPublicKeyPem,
        private readonly int $clockSkew = 300,
    ) {
        $this->clientPublicKeyPem = PemLoader::load($clientPublicKeyPem, 'client public key');

        $this->clientKey = JWKFactory::createFromKey(
            PemLoader::load($clientPrivateKeyPem, 'client private key')
        );
        $this->serverKey = JWKFactory::createFromKey(
            PemLoader::load($serverPublicKeyPem, 'server public key')
        );

        $signatureAlgorithms  = new AlgorithmManager([new ES256()]);
        $encryptionAlgorithms = new AlgorithmManager([new ECDHESA256KW(), new A256GCM()]);

        $this->jwsBuilder    = new JWSBuilder($signatureAlgorithms);
        $this->jwsVerifier   = new JWSVerifier($signatureAlgorithms);
        $this->jweBuilder    = new JWEBuilder($encryptionAlgorithms);
        $this->jweDecrypter  = new JWEDecrypter($encryptionAlgorithms, null);
        $this->jwsSerializer = new JwsCompactSerializer();
        $this->jweSerializer = new JweCompactSerializer();
    }

    public function encryptAndSign(array $data, string $path): string
    {
        $iat = time();

        $jwsPayload = json_encode([
            'path'   => $path,
            'data'   => json_encode($data, self::JSON_FLAGS),
            'method' => 'POST',
            'iat'    => $iat,
        ], self::JSON_FLAGS);

        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($jwsPayload)
            ->addSignature($this->clientKey, ['alg' => 'ES256', 'clientID' => $this->clientId])
            ->build();

        $signedJws = $this->jwsSerializer->serialize($jws, 0);

        $jwePayload = json_encode(['data' => $signedJws, 'iat' => $iat], self::JSON_FLAGS);

        $jwe = $this->jweBuilder
            ->create()
            ->withPayload($jwePayload)
            ->withSharedProtectedHeader(['alg' => 'ECDH-ES+A256KW', 'enc' => 'A256GCM'])
            ->addRecipient($this->serverKey)
            ->build();

        return $this->jweSerializer->serialize($jwe, 0);
    }

    public function decryptAndVerify(string $payload): array
    {
        $now = time();

        $jwe = $this->jweSerializer->unserialize($payload);

        if (! $this->jweDecrypter->decryptUsingKey($jwe, $this->clientKey, 0)) {
            throw new ShahkarApiException('Failed to decrypt the server response.');
        }

        $jwePayload = json_decode((string) $jwe->getPayload(), true);

        if (! is_array($jwePayload) || abs($now - (int) ($jwePayload['iat'] ?? 0)) > $this->clockSkew) {
            throw new ShahkarApiException('Server response (JWE) timestamp is missing or expired.');
        }

        $jws = $this->jwsSerializer->unserialize((string) $jwePayload['data']);

        if (! $this->jwsVerifier->verifyWithKey($jws, $this->serverKey, 0)) {
            throw new ShahkarApiException('Server response signature verification failed.');
        }

        $jwsPayload = json_decode((string) $jws->getPayload(), true);

        if (! is_array($jwsPayload) || abs($now - (int) ($jwsPayload['iat'] ?? 0)) > $this->clockSkew) {
            throw new ShahkarApiException('Server response (JWS) timestamp is missing or expired.');
        }

        $data = json_decode((string) $jwsPayload['data'], true);

        return is_array($data) ? $data : [];
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientPublicKey(): string
    {
        return $this->clientPublicKeyPem;
    }
}
