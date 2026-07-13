<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\Http\Responses\ApiResponse;

interface HttpClientInterface
{
    /**
     * Send a JSON POST request. The X-API-KEY header is added by the client.
     *
     * @param  array<string,mixed> $body
     */
    public function post(string $endpoint, array $body): ApiResponse;

    /**
     * Send a GET request. The X-API-KEY header is added by the client.
     */
    public function get(string $endpoint): ApiResponse;
}
