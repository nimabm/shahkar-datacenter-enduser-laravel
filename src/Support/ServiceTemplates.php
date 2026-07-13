<?php

namespace Shahkar\DataCenter\Support;

use InvalidArgumentException;

/**
 * Ready-made request payloads for the CLI tooling, mirroring
 * get_service_template() in sample_python/main.py. Values are sample data an
 * admin can edit before sending.
 */
class ServiceTemplates
{
    public const CUSTOMERS = ['real', 'legal'];
    public const SERVICES  = ['shared', 'vps', 'dedicated', 'colocation', 'cdn'];
    public const ACTIONS   = ['put', 'update', 'delete'];

    /**
     * @return array<string,mixed>
     */
    public static function for(string $action, ?string $customer = null, ?string $service = null): array
    {
        return match ($action) {
            'put'    => self::put($customer, $service),
            'update' => self::update($customer, $service),
            'delete' => ['id' => 'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM'],
            default  => throw new InvalidArgumentException("Unknown action: {$action}"),
        };
    }

    private static function address(): array
    {
        return [
            'provinceCode' => '021',
            'townshipName' => 'Tehran',
            'address'      => 'Azadi St., Shahid Nayyeri Alley, No. 18',
            'street2'      => 'Shahid Nayyeri Alley',
            'houseNumber'  => '18',
            'postalCode'   => '1345676543',
            'tel'          => '02122334455',
        ];
    }

    private static function put(?string $customer, ?string $service): array
    {
        $customer = self::assertOption($customer, self::CUSTOMERS, 'customer');
        $service  = self::assertOption($service, self::SERVICES, 'service');

        $payload = $customer === 'real'
            ? [
                'identificationType' => 0,
                'identificationNo'   => '0987654321',
                'address'            => self::address(),
            ]
            : [
                'identificationType'      => 5,
                'identificationNo'        => '33273340437',
                'mobileNumber'            => '09128964532',
                'agentIdentificationType' => 0,
                'agentIdentificationNo'   => '0072314567',
                'address'                 => self::address(),
            ];

        $payload['service'] = self::serviceBlock($service);

        return $payload;
    }

    private static function serviceBlock(string $service): array
    {
        $common = [
            'type'         => 35,
            'dataCenterId' => '34689999',
            'ips'          => '185.168.12.10-185.168.12.10',
            'bandwidth'    => 256,
            'startDate'    => '14050101',
            'endDate'      => '14051229',
        ];

        return match ($service) {
            'shared' => $common + [
                'dataCenterType' => 14,
                'hasSSL'         => 1,
                'hasIXP'         => 1,
                'urlList'        => 'cra.ir',
            ],
            'vps' => $common + [
                'dataCenterType'    => 11,
                'hasIXP'            => 1,
                'urlList'           => 'cra.ir',
                'centerName'        => 'Shaghayegh',
                'province'          => '021',
                'dataCenterAddress' => 'Tehran, Shariati St., Entrance No. 17',
            ],
            'dedicated', 'colocation' => $common + [
                'dataCenterType'    => $service === 'dedicated' ? 12 : 13,
                'hasIXP'            => 1,
                'centerName'        => 'Shaghayegh',
                'province'          => '021',
                'dataCenterAddress' => 'Tehran, Shariati St., Entrance No. 17',
                'lat'               => '35.689198',
                'lon'               => '51.388973',
                'rowIndex'          => 1,
                'racIndex'          => 1,
                'unitIndex'         => 1,
                'units'             => 4,
            ],
            'cdn' => $common + [
                'dataCenterType' => 15,
                'hasSSL'         => 1,
                'urlList'        => 'cra.ir',
            ],
        };
    }

    private static function update(?string $customer, ?string $service): array
    {
        $customer = self::assertOption($customer, self::CUSTOMERS, 'customer');
        $service  = self::assertOption($service, self::SERVICES, 'service');

        $payload = [
            'id'            => 'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
            'serviceNumber' => '34689658',
        ];

        if ($customer === 'legal') {
            $payload['customerUpdate'] = [
                'agentIdentificationType' => 0,
                'agentIdentificationNo'   => '0063222313',
            ];
        }

        $payload['addressUpdate'] = [
            'townshipName' => 'Firuzkuh',
            'address'      => 'Imam St., Shahid Mahdavi Alley, No. 8, Floor 1, Unit 2',
            'street2'      => 'Shahid Mahdavi Alley',
            'houseNumber'  => '8',
            'postalCode'   => '7654316543',
            'tel'          => '02178334455',
        ];

        $payload['serviceUpdate'] = match ($service) {
            'shared'                  => ['hasIXP' => 1, 'ips' => '185.168.12.11-185.168.12.11'],
            'vps'                     => ['bandwidth' => 512, 'ips' => '185.168.12.11-185.168.12.11'],
            'dedicated', 'colocation' => [
                'hasIXP'    => 1,
                'rowIndex'  => 10,
                'racIndex'  => 3,
                'unitIndex' => 12,
                'ips'       => '185.168.12.11-185.168.12.11',
            ],
            'cdn' => ['hasSSL' => 1, 'bandwidth' => 128, 'ips' => '185.168.12.11-185.168.12.11'],
        };

        return $payload;
    }

    /**
     * @param  array<int,string> $allowed
     */
    private static function assertOption(?string $value, array $allowed, string $label): string
    {
        if ($value === null || ! in_array($value, $allowed, true)) {
            throw new InvalidArgumentException(
                "Invalid {$label} '" . ($value ?? 'null') . "'. Expected one of: " . implode(', ', $allowed)
            );
        }

        return $value;
    }
}
