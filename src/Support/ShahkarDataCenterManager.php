<?php

namespace Shahkar\DataCenter\Support;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;
use Shahkar\DataCenter\Contracts\DataCenterApiV92Interface;
use Shahkar\DataCenter\Enums\ApiVersion;

/**
 * Entry point that hands back the API implementation for a chosen document
 * version. This is what the ShahkarDataCenter facade resolves to.
 *
 *   ShahkarDataCenter::version('9.2')->registerForNaturalPerson(...); // V9.2, no OTP
 *   ShahkarDataCenter::version('1.0')->registerForNaturalPerson(...); // new web service v1.0, OTP
 *   ShahkarDataCenter::registerForNaturalPerson(...);                 // the configured default
 *
 * Calls made without version() are forwarded to the default version, set via
 * `config('shahkar-datacenter.default_version')`.
 */
class ShahkarDataCenterManager
{
    public function __construct(
        private readonly Container $app,
        private readonly string $defaultVersion,
    ) {}

    /**
     * Resolve the implementation for a specific version.
     *
     * The union return type intentionally lists both contracts: each version's
     * methods and DTOs differ, so callers work against the concrete version they
     * asked for.
     */
    public function version(ApiVersion|string $version): DataCenterApiInterface|DataCenterApiV92Interface
    {
        $version = $version instanceof ApiVersion
            ? $version
            : (ApiVersion::tryFrom($version) ?? throw new InvalidArgumentException(
                "Unknown Shahkar Data Center API version [{$version}]."
            ));

        return match ($version) {
            ApiVersion::V1_0 => $this->app->make(DataCenterApiInterface::class),
            ApiVersion::V9_2 => $this->app->make(DataCenterApiV92Interface::class),
        };
    }

    /**
     * The implementation for the configured default version.
     */
    public function default(): DataCenterApiInterface|DataCenterApiV92Interface
    {
        return $this->version($this->defaultVersion);
    }

    /**
     * Forward calls made without an explicit version() to the default version.
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->default()->{$method}(...$arguments);
    }
}
