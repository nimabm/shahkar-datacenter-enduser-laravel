<?php

namespace Shahkar\DataCenter\Contracts;

interface CryptoServiceInterface
{
    /**
     * Sign the given data with the client key (JWS/ES256) and encrypt it for
     * the server (JWE/ECDH-ES+A256KW/A256GCM). Returns the compact JWE string
     * that goes into the `signedEncryptedPayload` field.
     *
     * @param  array<string,mixed> $data  The inner request payload.
     * @param  string              $path  The inner API path (e.g. /rest/shahkar/datacenter/put).
     */
    public function encryptAndSign(array $data, string $path): string;

    /**
     * Decrypt a server response (JWE) with the client key and verify the
     * server's signature (JWS), returning the inner decoded data.
     *
     * @return array<string,mixed>
     */
    public function decryptAndVerify(string $payload): array;

    /** The client identifier registered with the server. */
    public function getClientId(): string;

    /** The client public key (PEM) used to register with the server. */
    public function getClientPublicKey(): string;
}
