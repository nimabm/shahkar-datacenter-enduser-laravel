# `shahkar/datacenter` Library Documentation

Complete guide for using the Laravel package for the Shahkar "End-User Data Center" service.

The package supports **two API versions** of the service. They behave differently,
so each has its own complete set of samples below:

- [**Version 9.2**](#version-92--single-request-no-otp) — single request, **no OTP**.
- [**Version 1.0**](#version-10--two-step-otp) — the new web service, **two-step OTP**.

Jump straight to the version you use; the two sections are self-contained.

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

# Version selection (see "API Versions" below)
SHAHKAR_API_VERSION=9.2
SHAHKAR_RESELLER_CODE=526   # required by v9.2 only
```

---

## API Versions

The Shahkar "End-User Data Center" service exists in more than one revision, and the
request flows differ. This package ships one implementation per version, keyed by the
version number printed on that version's own document:

| Key     | Flow                                   | Person DTOs                                        | Endpoints                    |
|---------|----------------------------------------|----------------------------------------------------|------------------------------|
| `'9.2'` | **Single request, no OTP.** Full identity sent inline. | `NaturalPersonV92DTO`, `LegalPersonV92DTO`         | `rest/shahkar/{put,update,delete}` |
| `'1.0'` | **Two-step OTP.** `identificationNo` + OTP resolve identity. | `NaturalPersonDTO`, `LegalPersonDTO`               | `rest/shahkar/datacenter/{put,update,close}` |

### How you pick a version

**Option A — set a default** (used whenever you don't say otherwise):

```php
// config/shahkar-datacenter.php
'default_version' => env('SHAHKAR_API_VERSION', '9.2'),
```

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;

// Uses config('shahkar-datacenter.default_version')
ShahkarDataCenter::registerForNaturalPerson($person, $address, $service);
```

**Option B — choose per call** with `version('...')` (overrides the default):

```php
ShahkarDataCenter::version('9.2')->registerForNaturalPerson($personV92, $address, $service);
ShahkarDataCenter::version('1.0')->registerForNaturalPerson($person,    $address, $service);
```

> ⚠️ **Each version uses different person DTOs and method signatures.** A `version('9.2')`
> call needs a `NaturalPersonV92DTO`; a `version('1.0')` call needs a `NaturalPersonDTO`.
> Never mix them. When in doubt, always write `->version('...')` explicitly — the samples
> below all do.

### Adding a future version

1. Add a case to [`ApiVersion`](src/Enums/ApiVersion.php) keyed by the new document's version number.
2. Add a contract + service implementing that document's flow.
3. Register the binding + a `match` arm in
   [`ShahkarDataCenterServiceProvider`](src/ShahkarDataCenterServiceProvider.php) and
   [`ShahkarDataCenterManager`](src/Support/ShahkarDataCenterManager.php).

---

# Version 9.2 — single request, no OTP

Everything in this section is for `ShahkarDataCenter::version('9.2')`. Registration,
update, close and delete each complete in **one** request — there is no OTP step. The
full identity is sent inline via the V9.2 person DTOs.

> If you set `SHAHKAR_API_VERSION=9.2`, you can drop `->version('9.2')` and call the
> facade directly (e.g. `ShahkarDataCenter::registerForNaturalPerson(...)`). The samples
> keep `->version('9.2')` explicit for clarity.

### 9.2 — Register for a natural person

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;

$person = new NaturalPersonV92DTO(
    identificationNo: '0987654321',
    name:             'علی',
    family:           'صارمی',
    mobile:           '09127613814',
    // iranian defaults to true -> identificationType 0
    fatherName:       'یوسف',
    birthDate:        '13541101',   // Jalali YYYYMMDD
    birthPlace:       'خمین',
    certificateNo:    '10984',
    gender:           1,
    email:            'sample@smp.com',
);

$address = new AddressDTO(
    provinceCode: '021',
    address:      'خیابان مطهری، کوچه شهید احمدعلی نیری، پلاک 18، طبقه 5، واحد 10',
    houseNumber:  '18',
    postalCode:   '1345676543',
    townshipName: 'اسلامشهر',
    street2:      'کوچه شهید احمدعلی نیری',
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
    $response = ShahkarDataCenter::version('9.2')
        ->registerForNaturalPerson($person, $address, $service);

    if ($response->success) {
        echo "Registered. Service number: {$response->getServiceNumber()}";
    }
} catch (ShahkarApiException $e) {
    logger()->error('Shahkar error', ['message' => $e->getMessage(), 'body' => $e->getResponseBody()]);
}
```

**Foreign natural person:** pass `iranian: false` (this switches `identificationType` to `1`)
and add `nationality` + `universalNo`; `certificateNo` no longer applies:

```php
$person = new NaturalPersonV92DTO(
    identificationNo: '9345887457',
    name:             'JOHN',
    family:           'HOPKINS',
    mobile:           '09123713361',
    iranian:          false,
    nationality:      'USA',
    universalNo:      '154263652',
    fatherName:       'GEORGE',
    birthDate:        '19830813',
    birthPlace:       'AUSTIN',
    gender:           1,
    email:            'sample@smp.com',
);
```

### 9.2 — Register for a legal person

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonV92DTO;

// Iranian company -> identificationType 5, agent type 0
$legal = new LegalPersonV92DTO(
    identificationNo:        '33273340437',
    mobile:                  '09127601880',
    companyName:             'داده‌پردازان نوین',
    agentIdentificationNo:   '0072314567',
    // iranian defaults to true
    registrationNo:          '226623',
    companyType:             3,
    registrationDate:        '13910209',
    email:                   'sample@smp.com',
    agentFirstName:          'سعید',
    agentLastName:           'عمرانی',
    agentFatherName:         'علی',
    agentBirthDate:          '13601012',
    agentBirthCertificateNo: '1234',
    agentMobile:             '09121713545',
);

ShahkarDataCenter::version('9.2')->registerForLegalPerson($legal, $address, $service);
```

**Foreign company:** pass `iranian: false` (switches `identificationType` to `6` and the
agent type to `1`) and add `nationality` / `agentNationality`:

```php
$legal = new LegalPersonV92DTO(
    identificationNo:      '11165432503',
    mobile:                '09124443289',
    companyName:           'هاپکینز الکترونیک سبز',
    agentIdentificationNo: '962780723',
    iranian:               false,
    nationality:           'USA',
    registrationNo:        '65253232',
    companyType:           4,
    registrationDate:      '20011028',
    email:                 'sample@smp.com',
    agentFirstName:        'JOHN',
    agentLastName:         'HOPKINS',
    agentFatherName:       'GEORGE',
    agentNationality:      'ROU',
    agentBirthDate:        '19871123',
    agentMobile:           '09126996337',
);
```

> The `$service` above can be **any** service type — see
> [Service DTOs (shared)](#service-dtos-shared) for VPS, Dedicated/Colocation and CDN.

### 9.2 — Update a service

One request, no OTP. Send only the fields you want to change.

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\CustomerUpdateV92DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;

ShahkarDataCenter::version('9.2')->update(
    serviceId:      'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber:  '34689658',
    serviceUpdate:  new SharedWebHostingUpdateDTO('DC001', ips: '185.168.12.11-185.168.12.11', hasIXP: true),
    addressUpdate:  new AddressUpdateDTO(townshipName: 'فیروزکوه', postalCode: '7654316543'), // optional
    customerUpdate: new CustomerUpdateV92DTO(email: 'moghadam@smp.com'),                       // optional
);
```

The same `update()` is used for natural and legal persons — `customerUpdate` is where you
change person fields (e.g. `email`, `mobile`, `companyName`). See
[Service update DTOs](#service-dtos-shared) for other service types.

### 9.2 — Close a service

Sent to the update endpoint with `"close": 1`.

```php
ShahkarDataCenter::version('9.2')->close(
    serviceId:     'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM',
    serviceNumber: '54123',
);
```

### 9.2 — Delete a service

Permanently deletes the service (its own endpoint).

```php
ShahkarDataCenter::version('9.2')->delete(
    serviceId:     'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM',
    serviceNumber: '85231',
);
```

---

# Version 1.0 — two-step OTP

Everything in this section is for `ShahkarDataCenter::version('1.0')`. Registration and
update are a **two-step** process:

> - **Step 1:** send the data **without** an OTP → Shahkar sends a one-time code to the subscriber.
> - **Step 2:** repeat the **same** call with the same `requestId`, this time with the OTP filled in → the service is finalized.

Unlike v9.2, you do **not** send full personal details — Shahkar resolves the identity
from `identificationNo` + the OTP.

> If you set `SHAHKAR_API_VERSION=1.0`, you can drop `->version('1.0')` and call the facade
> directly. The samples keep `->version('1.0')` explicit for clarity.

### 1.0 — Register for a natural person (two steps)

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;

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

// ---------- Step 1: send WITHOUT otp ----------
$person = new NaturalPersonDTO(
    identificationNo: '0987654321',
);

try {
    $response = ShahkarDataCenter::version('1.0')
        ->registerForNaturalPerson($person, $address, $service);

    // Persist requestId — you MUST reuse it in step 2
    session(['shahkar_request_id' => $response->requestId]);
} catch (ShahkarApiException $e) {
    logger()->error('Shahkar step 1 error', ['message' => $e->getMessage()]);
}

// ---------- Step 2: same call WITH the received otp ----------
$personWithOtp = new NaturalPersonDTO(
    identificationNo: '0987654321',
    otp:              12341, // code the subscriber received
);

$response = ShahkarDataCenter::version('1.0')->registerForNaturalPerson(
    person:    $personWithOtp,
    address:   $address,
    service:   $service,
    requestId: session('shahkar_request_id'), // same requestId as step 1
);

if ($response->success) {
    echo "Registered. Service number: {$response->getServiceNumber()}";
}
```

### 1.0 — Register for a legal person (two OTPs)

A legal person requires **two** OTPs — one for the company SIM, one for the agent's SIM.

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;

// ---------- Step 1: WITHOUT otp/agentOtp ----------
$person = new LegalPersonDTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',   // legal person's mobile
    agentIdentificationNo: '0072314567',    // agent's national code
);

ShahkarDataCenter::version('1.0')->registerForLegalPerson($person, $address, $service);

// ---------- Step 2: WITH both OTPs ----------
$personWithOtp = new LegalPersonDTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',
    agentIdentificationNo: '0072314567',
    otp:                   1234,    // OTP sent to the legal person's mobile
    agentOtp:              56781,   // OTP sent to the agent's primary SIM
);

$response = ShahkarDataCenter::version('1.0')->registerForLegalPerson($personWithOtp, $address, $service);
```

> The `$service` can be any service type — see [Service DTOs (shared)](#service-dtos-shared).

### 1.0 — Update a service

The OTP is passed directly on the update call (no separate step 1).

**Natural person:**

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;

$response = ShahkarDataCenter::version('1.0')->updateForNaturalPerson(
    serviceId:     'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber: '34689658',
    otp:           12341,
    serviceUpdate: new SharedWebHostingUpdateDTO('DC001', ips: '185.168.12.11-185.168.12.11', hasIXP: true),
    addressUpdate: new AddressUpdateDTO(townshipName: 'Firuzkuh', postalCode: '7654316543'), // optional
);
```

**Legal person** (two OTPs + optional `customerUpdate`):

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateDTO;

$response = ShahkarDataCenter::version('1.0')->updateForLegalPerson(
    serviceId:      'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber:  '34689658',
    otp:            1234,
    agentOtp:       56781,
    serviceUpdate:  new VpsUpdateDTO('DC001', bandwidth: 512, ips: '185.168.12.11-185.168.12.11'),
    customerUpdate: new LegalPersonUpdateDTO(agentIdentificationNo: '0063222313'), // optional
);
```

### 1.0 — Close a service

```php
try {
    $response = ShahkarDataCenter::version('1.0')->close(
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

## Service DTOs (shared)

The **person** DTOs differ per version, but the **address** and **service** DTOs are the
same for both `version('9.2')` and `version('1.0')`. Build the `$service` you need and pass
it to that version's `register...` call, or the `...UpdateDTO` to its `update...` call.

### SharedWebHosting

```php
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;

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

$update = new SharedWebHostingUpdateDTO('DC001', ips: '185.168.12.11-185.168.12.11', hasIXP: true);
```

### VPS

```php
use Shahkar\DataCenter\DTOs\Service\VpsServiceDTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateDTO;

$service = new VpsServiceDTO(
    dataCenterId:      '34689999',
    centerName:        'Shaghayegh',
    dataCenterAddress: 'Tehran, Shariati St., Entrance No. 17, Ministry of Communications',
    ips:               '185.168.12.10-185.168.12.10',
    bandwidth:         256,
    startDate:         '13991211',
    endDate:           '13991212',
    province:          '021',
    hasIXP:            true,
    urlList:           'cra.ir',
);

$update = new VpsUpdateDTO('DC001', bandwidth: 512, ips: '185.168.12.11-185.168.12.11');
```

### DedicatedServer / Colocation

```php
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceDTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationUpdateDTO;
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

$update = new DedicatedColocationUpdateDTO(
    dataCenterId: 'DC001',
    rowIndex:     10,
    racIndex:     3,
    unitIndex:    12,
    ips:          '185.168.12.11-185.168.12.11',
    hasIXP:       true,
);
```

### CDN

```php
use Shahkar\DataCenter\DTOs\Service\CdnServiceDTO;
use Shahkar\DataCenter\DTOs\Service\CdnUpdateDTO;

$service = new CdnServiceDTO(
    dataCenterId: '34689999',
    ips:          '185.168.12.10-185.168.12.10',
    bandwidth:    256,
    startDate:    '13991211',
    urlList:      'cra.ir',
    endDate:      '13991212',
    hasSSL:       true,
);

$update = new CdnUpdateDTO('DC001', bandwidth: 128, ips: '185.168.12.11-185.168.12.11', hasSSL: true);
```

### Data Center Type Table

| Service type     | `dataCenterType` | Register DTO                    | Update DTO                     |
|------------------|------------------|---------------------------------|--------------------------------|
| SharedWebHosting | 14               | `SharedWebHostingServiceDTO`    | `SharedWebHostingUpdateDTO`    |
| VPS              | 11               | `VpsServiceDTO`                 | `VpsUpdateDTO`                 |
| DedicatedServer  | 12               | `DedicatedColocationServiceDTO` | `DedicatedColocationUpdateDTO` |
| Colocation       | 13               | `DedicatedColocationServiceDTO` | `DedicatedColocationUpdateDTO` |
| CDN              | 15               | `CdnServiceDTO`                 | `CdnUpdateDTO`                 |

The service type (`type`) is always `35` (Data Center) for every request; the DTOs set it
for you.

---

## Using IpRangeHelper

```php
use Shahkar\DataCenter\Support\IpRangeHelper;

$ranges = [
    '185.168.1.1-185.168.1.250',
    '185.168.2.1-185.168.2.100',
];

try {
    IpRangeHelper::validate($ranges); // no exception means valid
} catch (\InvalidArgumentException $e) {
    echo 'IP range error: ' . $e->getMessage();
}

$ipsString = IpRangeHelper::format($ranges);
// Result: "185.168.1.1-185.168.1.250,185.168.2.1-185.168.2.100"
```

---

## Error Handling

The same exceptions apply to both versions:

```php
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;

try {
    $response = ShahkarDataCenter::version('9.2')->registerForNaturalPerson(/* ... */);
} catch (ShahkarValidationException $e) {
    // HTTP 422 - invalid data
    logger()->warning('Validation failed', $e->getResponseBody() ?? []);
} catch (ShahkarApiException $e) {
    // Any other API error (4xx/5xx or connection failure)
    logger()->error('Shahkar error', [
        'code'    => $e->getCode(),
        'message' => $e->getMessage(),
        'body'    => $e->getResponseBody(),
    ]);
}
```

`ShahkarValidationException` extends `ShahkarApiException`, so catch the more specific type
first. `OtpRequiredException` is also available if you want to model the v1.0 OTP step
explicitly in your own flow.

---

## Dependency Injection (without the Facade)

Inject the contract for the version you need, or the manager to pick at runtime:

```php
use Shahkar\DataCenter\Contracts\DataCenterApiV92Interface;   // v9.2
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;      // v1.0
use Shahkar\DataCenter\Support\ShahkarDataCenterManager;      // either, chosen at runtime

class RegistersDataCenters
{
    public function __construct(
        private readonly DataCenterApiV92Interface $v92,
        private readonly DataCenterApiInterface    $v1,      // the v1.0 OTP flow
        private readonly ShahkarDataCenterManager  $shahkar,
    ) {}

    public function example(): void
    {
        // Directly against v9.2
        $this->v92->registerForNaturalPerson(/* NaturalPersonV92DTO */, $address, $service);

        // Or resolve a version dynamically
        $this->shahkar->version('1.0')->registerForNaturalPerson(/* NaturalPersonDTO */, $address, $service);
    }
}
```

---

## Mocking in tests

Mock the contract for whichever version your code uses:

```php
use Shahkar\DataCenter\Contracts\DataCenterApiV92Interface;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

$this->mock(DataCenterApiV92Interface::class, function ($mock) {
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

For the v1.0 flow, mock `DataCenterApiInterface` instead.

---

## Important Notes

- All IPs must be **public** and previously registered in Shahkar.
- For **natural** persons: an active primary SIM registered in their own name is mandatory.
- For **legal** persons: both a SIM registered to the legal person and the agent's primary SIM are required.
- This service cannot be registered for people **under 18 years old**.
- Service **transfer** is currently not supported.
- Dates must be **Jalali (Solar Hijri)** in `YYYYMMDD` format (e.g. `14030101`).
- The province must be sent as its **numeric code** (e.g. `021` for Tehran, not the province name).
- **v9.2 only:** `resellerCode` (config `reseller_code`) is sent on every request.

---

## Package Architecture

```
src/
├── Contracts/              # Interfaces (Dependency Inversion principle)
│   ├── DataCenterApiInterface.php      # v1.0 OTP flow ('1.0')
│   ├── DataCenterApiV92Interface.php   # v9.2 flow ('9.2')
│   ├── HttpClientInterface.php
│   └── ServiceDataInterface.php
├── DTOs/                   # Data Transfer Objects (type-safe)
│   ├── Address/
│   │   ├── AddressDTO.php               (shared)
│   │   └── AddressUpdateDTO.php         (shared)
│   ├── Person/
│   │   ├── NaturalPersonDTO.php         (natural person — v1.0 OTP)
│   │   ├── LegalPersonDTO.php           (legal person — v1.0 OTP)
│   │   ├── LegalPersonUpdateDTO.php     (v1.0)
│   │   ├── NaturalPersonV92DTO.php      (natural person — v9.2)
│   │   ├── LegalPersonV92DTO.php        (legal person — v9.2)
│   │   └── CustomerUpdateV92DTO.php     (customer update — v9.2)
│   └── Service/                         # all shared by both versions
│       ├── SharedWebHostingServiceDTO.php
│       ├── VpsServiceDTO.php
│       ├── DedicatedColocationServiceDTO.php
│       ├── CdnServiceDTO.php
│       ├── SharedWebHostingUpdateDTO.php
│       ├── VpsUpdateDTO.php
│       ├── DedicatedColocationUpdateDTO.php
│       └── CdnUpdateDTO.php
├── Enums/
│   ├── ApiVersion.php          # registered document versions
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
│   ├── DataCenterApiService.php        # v1.0 OTP flow ('1.0')
│   └── DataCenterApiServiceV92.php     # v9.2 flow ('9.2')
├── Support/
│   ├── ShahkarDataCenterManager.php    # resolves the version to use
│   ├── RequestIdGenerator.php
│   └── IpRangeHelper.php
└── Facades/
    └── ShahkarDataCenter.php
```

---

## Running the Tests

```bash
cd vendor/shahkar/datacenter
composer install
./vendor/bin/phpunit
```
