# `shahkar/datacenter` Library Documentation

Complete guide for using the Laravel package for the Shahkar "End-User Data Center" service.

---

## Installation & Setup

### 1. Install the package

```bash
composer require shahkar/datacenter
```

### 2. Publish the config file

```bash
php artisan vendor:publish --tag=shahkar-datacenter-config
```

This command creates the `config/shahkar-datacenter.php` file in your project.

### 3. Configure environment variables

Add the following values to your `.env` file:

```env
SHAHKAR_BASE_URL=https://api.shahkar.ir
SHAHKAR_USERNAME=your_username
SHAHKAR_PASSWORD=your_password
SHAHKAR_OPERATOR_ID=013
SHAHKAR_TIMEOUT=30
SHAHKAR_VERIFY_SSL=true
```

---

## Package Architecture

```
src/
├── Contracts/              # Interfaces (Dependency Inversion principle)
│   ├── DataCenterApiInterface.php
│   ├── HttpClientInterface.php
│   └── ServiceDataInterface.php
├── DTOs/                   # Data Transfer Objects (type-safe)
│   ├── Address/
│   │   ├── AddressDTO.php
│   │   └── AddressUpdateDTO.php
│   ├── Person/
│   │   ├── NaturalPersonDTO.php   (natural person)
│   │   ├── LegalPersonDTO.php     (legal person)
│   │   └── LegalPersonUpdateDTO.php
│   └── Service/
│       ├── SharedWebHostingServiceDTO.php
│       ├── VpsServiceDTO.php
│       ├── DedicatedColocationServiceDTO.php
│       ├── CdnServiceDTO.php
│       ├── SharedWebHostingUpdateDTO.php
│       ├── VpsUpdateDTO.php
│       ├── DedicatedColocationUpdateDTO.php
│       └── CdnUpdateDTO.php
├── Enums/
│   ├── DataCenterType.php
│   ├── IdentificationType.php
│   └── ServiceType.php
├── Exceptions/
│   ├── ShahkarApiException.php
│   ├── OtpRequiredException.php
│   └── ShahkarValidationException.php
├── Http/
│   ├── ShahkarHttpClient.php
│   └── Responses/ApiResponse.php
├── Services/
│   └── DataCenterApiService.php
├── Support/
│   ├── RequestIdGenerator.php
│   └── IpRangeHelper.php
└── Facades/
    └── ShahkarDataCenter.php
```

---

## Service Registration Flow (two steps)

> **Important:** Registering and updating a service is a **two-step** process:
> - **Step 1:** Send the data without an OTP → the system sends a one-time code.
> - **Step 2:** Resend the same data plus the received OTP → final registration.
>
> On step 1 the API returns a normal response and dispatches the OTP to the
> subscriber. Persist the `requestId` you used, then repeat the exact same call
> with the OTP populated and the same `requestId` to finalize.

---

## Code Examples

### Register SharedWebHosting for a natural person

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;

// ---------- Step 1: send without OTP ----------
$person = new NaturalPersonDTO(
    identificationNo: '0987654321',
);

$address = new AddressDTO(
    provinceCode: '021',
    address:      'Motahari St., Shahid Ahmad-Ali Nayyeri Alley, No. 18, Floor 5, Unit 10',
    houseNumber:  '18',
    postalCode:   '1345676543',
    townshipName: 'Eslamshahr',
    street2:      'Shahid Ahmad-Ali Nayyeri Alley',
    tel:          '02122334455',
);

$service = new SharedWebHostingServiceDTO(
    dataCenterId: '34689999',
    ips:          '185.168.12.10-185.168.12.10',
    bandwidth:    256,
    startDate:    '13991211',
    urlList:      'cra.ir',
    endDate:      '13991212',
    hasSSL:       true,
    hasIXP:       true,
);

try {
    $response = ShahkarDataCenter::registerForNaturalPerson($person, $address, $service);
    // Persist requestId for step 2
    session(['shahkar_request_id' => $response->requestId]);
} catch (ShahkarApiException $e) {
    // API error
    logger()->error('Shahkar API error', ['message' => $e->getMessage(), 'body' => $e->getResponseBody()]);
}


// ---------- Step 2: send with OTP ----------
$personWithOtp = new NaturalPersonDTO(
    identificationNo: '0987654321',
    otp:              12341, // code received by the subscriber
);

try {
    $response = ShahkarDataCenter::registerForNaturalPerson(
        person:    $personWithOtp,
        address:   $address,
        service:   $service,
        requestId: session('shahkar_request_id'),
    );

    if ($response->success) {
        $serviceNumber = $response->getServiceNumber();
        echo "Service registered successfully. Service number: {$serviceNumber}";
    }
} catch (ShahkarApiException $e) {
    logger()->error('Shahkar OTP error', ['message' => $e->getMessage()]);
}
```

---

### Register VPS for a legal person

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\VpsServiceDTO;

// ---------- Step 1 ----------
$person = new LegalPersonDTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',   // legal person's mobile
    agentIdentificationNo: '0072314567',    // agent's national code
);

$service = new VpsServiceDTO(
    dataCenterId:       '34689999',
    centerName:         'Shaghayegh',
    dataCenterAddress:  'Tehran, Shariati St., Entrance No. 17, Ministry of Communications',
    ips:                '185.168.12.10-185.168.12.10',
    bandwidth:          256,
    startDate:          '13991211',
    endDate:            '13991212',
    province:           '021',
    hasIXP:             true,
    urlList:            'cra.ir',
);

ShahkarDataCenter::registerForLegalPerson($person, $address, $service);


// ---------- Step 2 (with two OTPs) ----------
$personWithOtp = new LegalPersonDTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',
    agentIdentificationNo: '0072314567',
    otp:                   1234,    // OTP sent to the legal person's mobile
    agentOtp:              56781,   // OTP sent to the agent's primary SIM
);

$response = ShahkarDataCenter::registerForLegalPerson($personWithOtp, $address, $service);
```

---

### Register DedicatedServer / Colocation

```php
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceDTO;
use Shahkar\DataCenter\Enums\DataCenterType;

$service = new DedicatedColocationServiceDTO(
    dataCenterId:      '34689999',
    centerName:        'Shaghayegh',
    dataCenterAddress: 'Tehran, Shariati St., Entrance No. 17, Ministry of Communications',
    ips:               '185.168.12.10-185.168.12.10',
    bandwidth:         256,
    startDate:         '13991211',
    lat:               '35.689198',
    lon:               '51.388973',
    rowIndex:          1,
    racIndex:          1,
    unitIndex:         1,
    dataCenterType:    DataCenterType::DedicatedServer, // or DataCenterType::Colocation
    endDate:           '13991212',
    province:          '021',
    hasIXP:            true,
    units:             4,
);

ShahkarDataCenter::registerForNaturalPerson($person, $address, $service);
```

---

### Register CDN

```php
use Shahkar\DataCenter\DTOs\Service\CdnServiceDTO;

$service = new CdnServiceDTO(
    dataCenterId: '34689999',
    ips:          '185.168.12.10-185.168.12.10',
    bandwidth:    256,
    startDate:    '13991211',
    urlList:      'cra.ir',
    endDate:      '13991212',
    hasSSL:       true,
);

ShahkarDataCenter::registerForNaturalPerson($person, $address, $service);
```

---

### Update a service

#### SharedWebHosting — natural person

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;

$addressUpdate = new AddressUpdateDTO(
    townshipName: 'Firuzkuh',
    address:      'Imam St., Shahid Mahdavi Alley, No. 8, Floor 1, Unit 2',
    street2:      'Shahid Mahdavi Alley',
    houseNumber:  '8',
    postalCode:   '7654316543',
    tel:          '02178334455',
);

$serviceUpdate = new SharedWebHostingUpdateDTO(
    dataCenterId: 'DC001',
    ips:          '185.168.12.11-185.168.12.11',
    hasIXP:       true,
);

$response = ShahkarDataCenter::updateForNaturalPerson(
    serviceId:     'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber: '34689658',
    otp:           12341,
    serviceUpdate: $serviceUpdate,
    addressUpdate: $addressUpdate,
);
```

#### VPS — legal person

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateDTO;

$customerUpdate = new LegalPersonUpdateDTO(
    agentIdentificationNo: '0063222313',
);

$serviceUpdate = new VpsUpdateDTO(
    dataCenterId: 'DC001',
    bandwidth:    512,
    ips:          '185.168.12.11-185.168.12.11',
);

$response = ShahkarDataCenter::updateForLegalPerson(
    serviceId:      'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber:  '34689658',
    otp:            1234,
    agentOtp:       56781,
    serviceUpdate:  $serviceUpdate,
    addressUpdate:  $addressUpdate,
    customerUpdate: $customerUpdate,
);
```

#### DedicatedServer/Colocation — update

```php
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationUpdateDTO;

$serviceUpdate = new DedicatedColocationUpdateDTO(
    dataCenterId: 'DC001',
    rowIndex:     10,
    racIndex:     3,
    unitIndex:    12,
    ips:          '185.168.12.11-185.168.12.11',
    hasIXP:       true,
);

ShahkarDataCenter::updateForNaturalPerson(
    serviceId:     'service-uuid',
    serviceNumber: '34689658',
    otp:           12341,
    serviceUpdate: $serviceUpdate,
);
```

#### CDN — update

```php
use Shahkar\DataCenter\DTOs\Service\CdnUpdateDTO;

$serviceUpdate = new CdnUpdateDTO(
    dataCenterId: 'DC001',
    bandwidth:    128,
    ips:          '185.168.12.11-185.168.12.11',
    hasSSL:       true,
);

ShahkarDataCenter::updateForNaturalPerson(
    serviceId:     'service-uuid',
    serviceNumber: '34689658',
    otp:           12341,
    serviceUpdate: $serviceUpdate,
);
```

---

### Close a service

```php
try {
    $response = ShahkarDataCenter::close(
        serviceId: 'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM'
    );

    if ($response->success) {
        echo 'Service closed successfully.';
    }
} catch (ShahkarApiException $e) {
    logger()->error('Close failed', ['error' => $e->getMessage()]);
}
```

---

## Using IpRangeHelper

```php
use Shahkar\DataCenter\Support\IpRangeHelper;

// Validate the ranges before sending
$ranges = [
    '185.168.1.1-185.168.1.250',
    '185.168.2.1-185.168.2.100',
];

try {
    IpRangeHelper::validate($ranges); // no exception means valid
} catch (\InvalidArgumentException $e) {
    echo 'IP range error: ' . $e->getMessage();
}

// Convert the array into the API format
$ipsString = IpRangeHelper::format($ranges);
// Result: "185.168.1.1-185.168.1.250,185.168.2.1-185.168.2.100"
```

---

## Error Handling

```php
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;

try {
    $response = ShahkarDataCenter::registerForNaturalPerson(...);
} catch (ShahkarValidationException $e) {
    // HTTP 422 - invalid data
    $errors = $e->getResponseBody();
    logger()->warning('Validation failed', $errors ?? []);

} catch (ShahkarApiException $e) {
    // Any other API error (4xx/5xx or connection failure)
    logger()->error('Shahkar error', [
        'code'    => $e->getCode(),
        'message' => $e->getMessage(),
        'body'    => $e->getResponseBody(),
    ]);
}
```

`ShahkarValidationException` extends `ShahkarApiException`, so catch the more
specific type first. `OtpRequiredException` is also available for callers that
want to model the OTP step explicitly in their own flow.

---

## Dependency Injection (without the Facade)

```php
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;

class DataCenterRegistrationService
{
    public function __construct(
        private readonly DataCenterApiInterface $dataCenterApi
    ) {}

    public function register(): void
    {
        $response = $this->dataCenterApi->registerForNaturalPerson(
            person:  new NaturalPersonDTO('0987654321'),
            address: new AddressDTO('021', 'Azadi Street', '10', '1234567890'),
            service: new SharedWebHostingServiceDTO('DC001', '1.2.3.4-1.2.3.4', 256, '14030101', 'cra.ir'),
        );
    }
}
```

---

## Mocking in tests

```php
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

// In your TestCase
$this->mock(DataCenterApiInterface::class, function ($mock) {
    $mock->shouldReceive('registerForNaturalPerson')
         ->once()
         ->andReturn(new ApiResponse(
             success:    true,
             statusCode: 200,
             body:       ['serviceNumber' => '12345'],
             requestId:  'test-req-001',
         ));
});
```

---

## Data Center Type Table

| Service type | `dataCenterType` value | Register DTO | Update DTO |
|--------------|------------------------|--------------|------------|
| SharedWebHosting | 14 | `SharedWebHostingServiceDTO` | `SharedWebHostingUpdateDTO` |
| VPS | 11 | `VpsServiceDTO` | `VpsUpdateDTO` |
| DedicatedServer | 12 | `DedicatedColocationServiceDTO` | `DedicatedColocationUpdateDTO` |
| Colocation | 13 | `DedicatedColocationServiceDTO` | `DedicatedColocationUpdateDTO` |
| CDN | 15 | `CdnServiceDTO` | `CdnUpdateDTO` |

The service type (`type`) is always `35` (Data Center) for every request.

---

## Important Notes

- All IPs must be **public** and previously registered in Shahkar.
- For **natural** persons: an active primary SIM registered in their own name is mandatory.
- For **legal** persons: both a SIM registered to the legal person and the agent's primary SIM are required.
- This service cannot be registered for people **under 18 years old**.
- Service **transfer** is currently not supported.
- Dates must be **Jalali (Solar Hijri)** in `YYYYMMDD` format (e.g. `14030101`).
- The province must be sent as its **numeric code** (e.g. `021` for Tehran, not the province name).

---

## Running the Tests

```bash
cd vendor/shahkar/datacenter
composer install
./vendor/bin/phpunit
```
