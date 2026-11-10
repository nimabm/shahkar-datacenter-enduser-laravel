<?php

namespace Shahkar\DataCenter\Support;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Shahkar\DataCenter\Contracts\DataCenterApiV12Interface;
use Shahkar\DataCenter\Contracts\DataCenterApiV1Interface;
use Shahkar\DataCenter\Contracts\DataCenterApiV92Interface;
use Shahkar\DataCenter\Contracts\DataCenterApiV94Interface;
use Shahkar\DataCenter\Enums\ApiVersion;

/**
 * Entry point for choosing which API version to talk to. This is what the
 * ShahkarDataCenter facade resolves to.
 *
 * Prefer the typed accessors — they return a concrete contract, so IDEs and
 * static analysis know exactly which methods and DTOs apply:
 *
 *   ShahkarDataCenter::v94()->registerForNaturalPerson(...); // v9.4, single-step, publicIps/privateIps
 *   ShahkarDataCenter::v12()->registerForNaturalPerson(...); // v1.2, two-step OTP, publicIps/privateIps
 *   ShahkarDataCenter::v92()->registerForNaturalPerson(...); // v9.2, single-step, ips
 *   ShahkarDataCenter::v1()->registerForNaturalPerson(...);  // v1.0, two-step OTP, ips
 *
 * Use version()/default() only when the version is decided at runtime (e.g. read
 * from config); they return a union of all contracts.
 */
class ShahkarDataCenterManager
{
    public function __construct(
        private readonly Container $app,
        private readonly string $defaultVersion,
    ) {}

    /**
     * Version 9.4 — single request, no OTP, publicIps/privateIps.
     */
    public function v94(): DataCenterApiV94Interface
    {
        return $this->app->make(DataCenterApiV94Interface::class);
    }

    /**
     * Version 1.2 — new authentication, two-step OTP, publicIps/privateIps.
     */
    public function v12(): DataCenterApiV12Interface
    {
        return $this->app->make(DataCenterApiV12Interface::class);
    }

    /**
     * Version 9.2 — single request, no OTP, single ips field.
     */
    public function v92(): DataCenterApiV92Interface
    {
        return $this->app->make(DataCenterApiV92Interface::class);
    }

    /**
     * Version 1.0 — new authentication, two-step OTP, single ips field.
     */
    public function v1(): DataCenterApiV1Interface
    {
        return $this->app->make(DataCenterApiV1Interface::class);
    }

    /**
     * Resolve a version chosen at runtime. Returns a union of all contracts, so
     * prefer the typed accessors when the version is known at author time.
     */
    public function version(
        ApiVersion|string $version
    ): DataCenterApiV1Interface|DataCenterApiV12Interface|DataCenterApiV92Interface|DataCenterApiV94Interface {
        $version = $version instanceof ApiVersion
            ? $version
            : (ApiVersion::tryFrom($version) ?? throw new InvalidArgumentException(
                "Unknown Shahkar Data Center API version [{$version}]."
            ));

        return match ($version) {
            ApiVersion::V1_0 => $this->v1(),
            ApiVersion::V1_2 => $this->v12(),
            ApiVersion::V9_2 => $this->v92(),
            ApiVersion::V9_4 => $this->v94(),
        };
    }

    /**
     * The implementation for the version configured in
     * `shahkar-datacenter.default_version`.
     */
    public function default(): DataCenterApiV1Interface|DataCenterApiV12Interface|DataCenterApiV92Interface|DataCenterApiV94Interface
    {
        return $this->version($this->defaultVersion);
    }
}
