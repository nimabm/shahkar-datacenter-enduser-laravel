# `shahkar/datacenter` Library Documentation

Laravel package for the NSCRA "End-User Data Center" service (Shahkar new
authentication). It handles the full secure transport the API requires:
signed + encrypted requests, `X-API-KEY` auth, and asynchronous result polling.

---

## How the API works (read this first)

Every data-center request goes through a secure envelope, **not** a plain JSON
POST:

1. The inner payload (person + address + service) is **signed** with your client
   key (JWS / ES256).
2. The signed token is **encrypted** for the server (JWE / ECDH-ES+A256KW /
   A256GCM) using the server's public key.
3. You POST `{"signedEncryptedPayload": "<jwe>"}` to the transport endpoint with
   an `X-API-KEY` header.
4. The API **queues** the request and returns a `trackingId`. You then poll
   `GET /dc/status/{trackingId}`; its `responseBody` is itself encrypted and is
   decrypted + signature-verified for you.

The package does all of this — you work with plain DTOs and get an `ApiResponse`.

### Prerequisites

- An **EC P-256 (prime256v1) key pair** for your client.
- Your client public key **registered** with the server (2-step OTP — see below).
- Your `X-API-KEY`, `client_id`, and `provider_code`.

The **server public key** ships bundled with the package, so you don't need to
configure it. Override it only if NSCRA gives you a different one (e.g. a
separate test environment) via `SHAHKAR_SERVER_PUBLIC_KEY`.

---

## Installation

```bash
composer require shahkar/datacenter
php artisan vendor:publish --tag=shahkar-datacenter-config
```

### Generate your client key pair (once)

```bash
openssl ecparam -name prime256v1 -genkey -noout -out client_private.pem
openssl ec -in client_private.pem -pubout -out client_public.pem
```

### Environment variables

```env
SHAHKAR_BASE_URL=https://nscra.ir/api/1.0/external
SHAHKAR_API_KEY=your_api_key
SHAHKAR_CLIENT_ID=your_client_id
SHAHKAR_PROVIDER_CODE=1234

# Each key may be the PEM content OR an absolute path to a .pem file
SHAHKAR_CLIENT_PRIVATE_KEY=/secure/path/client_private.pem
SHAHKAR_CLIENT_PUBLIC_KEY=/secure/path/client_public.pem
# Optional — the server public key is bundled with the package by default.
# SHAHKAR_SERVER_PUBLIC_KEY=/secure/path/PublicKey.pem

SHAHKAR_TIMEOUT=30
SHAHKAR_VERIFY_SSL=true
SHAHKAR_CLOCK_SKEW=300
```

---

## Step 1 — Register your public key (2-step OTP)

Before sending any service request, your client public key must be registered.

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;

// 1) Request an OTP (sent to your registered number)
ShahkarDataCenter::registerKey();

// 2) Confirm with the received OTP
$response = ShahkarDataCenter::registerKey('123456');
```

---

## Step 2 — Register a service

Registration is a **two-step OTP** flow *and* asynchronous:

- **Call 1:** send the data without an OTP → the API sends an OTP to the
  subscriber and returns a `trackingId`.
- **Call 2:** resend the **same data + the OTP** and the **same `requestId`**.
- Then **poll** `checkStatus($trackingId)` for the final result.

### SharedWebHosting — natural person

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;

$address = new AddressDTO(
    provinceCode: '021',
    address:      'Motahari St., Shahid Nayyeri Alley, No. 18, Floor 5, Unit 10',
    houseNumber:  '18',
    postalCode:   '1345676543',
    townshipName: 'Eslamshahr',
    street2:      'Shahid Nayyeri Alley',
    tel:          '02122334455',
);

$service = new SharedWebHostingServiceDTO(
    dataCenterId: '34689999',
    ips:          '185.168.12.10-185.168.12.10',
    bandwidth:    256,
    startDate:    '14050101',
    urlList:      'cra.ir',
    endDate:      '14051229',
    hasSSL:       true,
    hasIXP:       true,
);

// ---------- Call 1: without OTP ----------
try {
    $response = ShahkarDataCenter::registerForNaturalPerson(
        person:  new NaturalPersonDTO(identificationNo: '0987654321'),
        address: $address,
        service: $service,
    );

    // Persist BOTH values for step 2 + polling
    session([
        'shahkar_request_id'  => $response->requestId,
        'shahkar_tracking_id' => $response->getTrackingId(),
    ]);
} catch (ShahkarApiException $e) {
    logger()->error('Shahkar error', ['message' => $e->getMessage(), 'body' => $e->getResponseBody()]);
}

// ---------- Call 2: same data + OTP + same requestId ----------
$response = ShahkarDataCenter::registerForNaturalPerson(
    person:    new NaturalPersonDTO(identificationNo: '0987654321', otp: 12341),
    address:   $address,
    service:   $service,
    requestId: session('shahkar_request_id'),
);

// ---------- Poll for the final result ----------
$result = ShahkarDataCenter::checkStatus(session('shahkar_tracking_id'));

if ($result->success && $result->getServiceNumber()) {
    echo "Service registered. Number: {$result->getServiceNumber()}";
}
```

### VPS — legal person (two OTPs)

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\VpsServiceDTO;

$service = new VpsServiceDTO(
    dataCenterId:      '34689999',
    centerName:        'Shaghayegh',
    dataCenterAddress: 'Tehran, Shariati St., Entrance No. 17, Ministry of Communications',
    ips:               '185.168.12.10-185.168.12.10',
    bandwidth:         256,
    startDate:         '14050101',
    endDate:           '14051229',
    province:          '021',
    hasIXP:            true,
    urlList:           'cra.ir',
);

// Call 1 (no OTPs)
ShahkarDataCenter::registerForLegalPerson(
    person:  new LegalPersonDTO('33273340437', '09128964532', '0072314567'),
    address: $address,
    service: $service,
);

// Call 2 (otp = legal person's SIM, agentOtp = agent's primary SIM)
ShahkarDataCenter::registerForLegalPerson(
    person: new LegalPersonDTO(
        identificationNo:      '33273340437',
        mobileNumber:          '09128964532',
        agentIdentificationNo: '0072314567',
        otp:                   1234,
        agentOtp:              56781,
    ),
    address:   $address,
    service:   $service,
    requestId: $savedRequestId,
);
```

### DedicatedServer / Colocation

```php
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceDTO;
use Shahkar\DataCenter\Enums\DataCenterType;

$service = new DedicatedColocationServiceDTO(
    dataCenterId:      '34689999',
    centerName:        'Shaghayegh',
    dataCenterAddress: 'Tehran, Shariati St., Entrance No. 17',
    ips:               '185.168.12.10-185.168.12.10',
    bandwidth:         256,
    startDate:         '14050101',
    lat:               '35.689198',
    lon:               '51.388973',
    rowIndex:          1,
    racIndex:          1,
    unitIndex:         1,
    dataCenterType:    DataCenterType::DedicatedServer, // or DataCenterType::Colocation
    endDate:           '14051229',
    province:          '021',
    hasIXP:            true,
    units:             4,
);
```

### CDN

```php
use Shahkar\DataCenter\DTOs\Service\CdnServiceDTO;

$service = new CdnServiceDTO(
    dataCenterId: '34689999',
    ips:          '185.168.12.10-185.168.12.10',
    bandwidth:    256,
    startDate:    '14050101',
    urlList:      'cra.ir',
    endDate:      '14051229',
    hasSSL:       true,
);
```

---

## Update a service

Same two-step OTP + polling model. `serviceId` (`id`) and `serviceNumber`
identify the service; call once without `otp` to receive it, then again with it.

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;

// Natural person
$response = ShahkarDataCenter::updateForNaturalPerson(
    serviceId:     'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber: '34689658',
    serviceUpdate: new SharedWebHostingUpdateDTO('DC001', ips: '185.168.12.11-185.168.12.11', hasIXP: true),
    otp:           12341, // omit on the first call
    addressUpdate: new AddressUpdateDTO(townshipName: 'Firuzkuh', tel: '02178334455'),
);
```

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateDTO;

// Legal person
ShahkarDataCenter::updateForLegalPerson(
    serviceId:      'service-uuid',
    serviceNumber:  '34689658',
    serviceUpdate:  new VpsUpdateDTO('DC001', bandwidth: 512, ips: '185.168.12.11-185.168.12.11'),
    otp:            1234,
    agentOtp:       56781,
    customerUpdate: new LegalPersonUpdateDTO(agentIdentificationNo: '0063222313'),
);
```

`DedicatedColocationUpdateDTO` and `CdnUpdateDTO` work the same way.

---

## Close (delete) a service

```php
$response = ShahkarDataCenter::delete(serviceId: 'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM');
// close() is kept as an alias of delete()
```

---

## Polling results — `checkStatus()`

```php
$result = ShahkarDataCenter::checkStatus($trackingId);

$result->success;              // HTTP-level success of the status call
$result->getDecrypted();       // decrypted + signature-verified responseBody (array|null)
$result->getServiceNumber();   // convenience accessor
$result->getMessage();         // message from the decrypted body / raw body
```

---

## IpRangeHelper

```php
use Shahkar\DataCenter\Support\IpRangeHelper;

$ranges = ['185.168.1.1-185.168.1.250', '185.168.2.1-185.168.2.100'];

IpRangeHelper::validate($ranges);        // throws InvalidArgumentException on overlap/invalid
$ips = IpRangeHelper::format($ranges);   // "185.168.1.1-185.168.1.250,185.168.2.1-185.168.2.100"
```

---

## Error handling

```php
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;

try {
    $response = ShahkarDataCenter::registerForNaturalPerson(...);
} catch (ShahkarValidationException $e) {   // HTTP 422
    logger()->warning('Validation failed', $e->getResponseBody() ?? []);
} catch (ShahkarApiException $e) {           // other 4xx/5xx, connection, or crypto failure
    logger()->error('Shahkar error', [
        'code'    => $e->getCode(),
        'message' => $e->getMessage(),
        'body'    => $e->getResponseBody(),
    ]);
}
```

Business errors from Shahkar (e.g. code `635` = required primary SIM is not
active/registered) are returned inside the **decrypted status result**
(`checkStatus()->getDecrypted()`), not thrown.

---

## Dependency Injection (without the Facade)

```php
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;

class RegistrationService
{
    public function __construct(private readonly DataCenterApiInterface $api) {}

    public function run(): void
    {
        $this->api->registerForNaturalPerson(
            person:  new NaturalPersonDTO('0987654321'),
            address: new AddressDTO('021', 'Azadi Street', '10', '1234567890'),
            service: new SharedWebHostingServiceDTO('DC001', '1.2.3.4-1.2.3.4', 256, '14050101', 'cra.ir'),
        );
    }
}
```

## Mocking in tests

```php
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

$this->mock(DataCenterApiInterface::class, function ($mock) {
    $mock->shouldReceive('registerForNaturalPerson')
         ->once()
         ->andReturn(new ApiResponse(true, 200, ['data' => ['trackingId' => 'TRK-1']]));
});
```

---

## Data Center Type table

| Service type | `dataCenterType` | Register DTO | Update DTO |
|--------------|------------------|--------------|------------|
| SharedWebHosting | 14 | `SharedWebHostingServiceDTO` | `SharedWebHostingUpdateDTO` |
| VPS | 11 | `VpsServiceDTO` | `VpsUpdateDTO` |
| DedicatedServer | 12 | `DedicatedColocationServiceDTO` | `DedicatedColocationUpdateDTO` |
| Colocation | 13 | `DedicatedColocationServiceDTO` | `DedicatedColocationUpdateDTO` |
| CDN | 15 | `CdnServiceDTO` | `CdnUpdateDTO` |

`type` (service type) is always `35` (Data Center).

---

## Important notes

- All IPs must be **public** and previously registered in Shahkar.
- Natural persons need an active **primary SIM** in their own name.
- Legal persons need a SIM registered to the legal person **and** the agent's primary SIM.
- Not available for people **under 18**, or for non-Iranian persons.
- Service **transfer** is not supported.
- Dates are **Jalali** in `YYYYMMDD` format (e.g. `14050101`).
- Province is a **numeric code** (e.g. `021` for Tehran), not a name.
- `requestId` is generated as `providerCode + Tehran timestamp`. Reuse the same
  `requestId` across both OTP steps of a single request.

---

## CLI tools (Artisan)

Two Artisan commands are provided for manual/admin use and for testing the web
service from the terminal — similar to the reference `sample_python/main.py`.

### `shahkar:keygen` — generate a client key pair

```bash
# Print the PEMs
php artisan shahkar:keygen

# Or write them to files (private key is chmod 600)
php artisan shahkar:keygen --path=/secure/keys
```

### `shahkar:datacenter` — interactive console

```bash
php artisan shahkar:datacenter
```

A menu-driven tool that lets an admin:

- generate a key pair,
- register the public key (2-step OTP),
- register / update / delete a service — each with a ready-made sample payload
  (or your own JSON file), the two OTP steps, and status polling,
- check a request status by tracking id.

It uses your configured credentials and keys, signs + encrypts every request,
and prints the raw and decrypted responses so you can inspect exactly what the
API returns.

> For scripting, `DataCenterApiInterface::sendRaw('put'|'update'|'delete', $data)`
> sends an arbitrary (already-shaped) payload without going through the DTOs.

---

## Running the tests (Docker)

```bash
docker run --rm -v "$PWD":/app -w /app composer:latest composer install
docker run --rm -v "$PWD":/app -w /app php:8.2-cli vendor/bin/phpunit
```

The reference implementation this package mirrors lives in `sample_python/`.
