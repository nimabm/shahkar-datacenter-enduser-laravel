<?php

namespace Shahkar\DataCenter\Contracts;

use Shahkar\DataCenter\Http\Responses\ApiResponse;

interface HttpClientInterface
{
    public function post(string $endpoint, array $payload): ApiResponse;
}
