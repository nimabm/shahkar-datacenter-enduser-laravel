# `shahkar/datacenter` Library Documentation

Complete guide for using the Laravel package for the Shahkar "End-User Data Center" service.

The package covers two things:

- The **Data Center** web service, in four API versions:
  - [**Version 9.4**](#version-94--single-request-no-otp-publicips--privateips) — single request, **no OTP**, `publicIps` / `privateIps`. **(default)**
  - [**Version 1.2**](#version-12--two-step-otp-publicips--privateips) — new authentication, **two-step OTP**, `publicIps` / `privateIps`.
  - [**Version 9.2**](#version-92--single-request-no-otp) — single request, **no OTP**, single `ips` field (superseded by 9.4).
  - [**Version 1.0**](#version-10--two-step-otp) — new authentication, **two-step OTP**, single `ips` field (superseded by 1.2).
- The standalone [**IP Registration** (`putIP`) service](#ip-registration-service-putip--shahkarip) — a separate
  Shahkar service for declaring the IP ranges an operator advertises. Accessed via its own
  `ShahkarIp` facade; not part of the Data Center flow.
- The standalone [**Estelaam** identity-inquiry service](#estelaam-identity-inquiry-service--shahkarinquiry) —
  verifies a person's identity (and postal code) against Shahkar's registry. Accessed via its
  own `ShahkarInquiry` facade; not part of the Data Center flow.
- The standalone [**Reseller Code** service](#reseller-code-service--shahkarreseller) — registers,
  updates, transfers, closes and deletes a reseller's sales code (service type 30). Accessed via
  its own `ShahkarReseller` facade; not part of the Data Center flow.

Jump straight to what you need; the sections are self-contained.

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
SHAHKAR_API_VERSION=9.4
SHAHKAR_RESELLER_CODE=526   # required by v9.4 / v9.2 only
```

---

## API Versions

The Shahkar "End-User Data Center" service exists in more than one revision, and the
request flows differ. This package ships one implementation per version, keyed by the
version number printed on that version's own document:

| Key     | Accessor | Flow                                   | Person DTOs                                        | Service DTOs | Endpoints                    |
|---------|----------|----------------------------------------|----------------------------------------------------|--------------|------------------------------|
| `'9.4'` | `v94()`  | **Single request, no OTP.** Full identity sent inline. | `NaturalPersonV92DTO`, `LegalPersonV92DTO`         | `*V94DTO` (`publicIps` / `privateIps`) | `rest/shahkar/{put,update,delete}` |
| `'1.2'` | `v12()`  | **Two-step OTP.** `identificationNo` + OTP resolve identity. | `NaturalPersonV1DTO`, `LegalPersonV1DTO`               | `*V12DTO` (`publicIps` / `privateIps`) | `rest/shahkar/datacenter/{put,update,close}` |
| `'9.2'` | `v92()`  | **Single request, no OTP.** Full identity sent inline. | `NaturalPersonV92DTO`, `LegalPersonV92DTO`         | unsuffixed (`ips`) | `rest/shahkar/{put,update,delete}` |
| `'1.0'` | `v1()`   | **Two-step OTP.** `identificationNo` + OTP resolve identity. | `NaturalPersonV1DTO`, `LegalPersonV1DTO`               | unsuffixed (`ips`) | `rest/shahkar/datacenter/{put,update,close}` |

9.4 and 1.2 (both revised 21/06/1405) replace the single `ips` field with **`publicIps`**
(required) and **`privateIps`** (optional). Their person fields didn't change, so they
reuse the 9.2 and 1.0 person DTOs. See [What changed in 9.4 / 1.2](#what-changed-in-94--12).

### How you pick a version

**Preferred — typed accessors.** Call `v94()`, `v12()`, `v92()` or `v1()`; each returns the
concrete contract for that version, so your IDE and static analysis know exactly which
methods and DTOs apply:

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;

ShahkarDataCenter::v94()->registerForNaturalPerson($personV92, $address, $serviceV94); // v9.4
ShahkarDataCenter::v12()->registerForNaturalPerson($personV1,  $address, $serviceV12); // v1.2
ShahkarDataCenter::v92()->registerForNaturalPerson($personV92, $address, $service);    // v9.2
ShahkarDataCenter::v1()->registerForNaturalPerson($personV1,   $address, $service);    // v1.0
```

**Dynamic — when the version is decided at runtime** (e.g. read from config), use
`version(...)` or `default()`. These return a union of all contracts, so reach for them
only when you can't name the version at author time:

```php
// config/shahkar-datacenter.php  ->  'default_version' => env('SHAHKAR_API_VERSION', '9.4')
ShahkarDataCenter::default()->registerForNaturalPerson(...);        // the configured version
ShahkarDataCenter::version($someVersion)->registerForNaturalPerson(...); // '9.4' | '1.2' | '9.2' | '1.0' | ApiVersion
```

> ⚠️ **Each version uses different DTOs and method signatures.** `v94()` only accepts the
> `*V94DTO` service classes and `v12()` only the `*V12DTO` ones; passing an older `ips`-based
> DTO throws a `TypeError`. Never mix them — that's exactly why the typed accessors exist:
> they stop you from passing the wrong DTO by accident.

### What changed in 9.4 / 1.2

Both documents (9.3 / 1.1 added the IP-registration rules, 9.4 / 1.2 the public/private split):

- **`ips` → `publicIps` + `privateIps`** in every service and service-update payload.
  - `publicIps` — required on registration. Public IPs only, and each range must sit inside
    a range the operator registered via [`ShahkarIp::put()`](#ip-registration-service-putip--shahkarip)
    or received from an assignable data center.
  - `privateIps` — optional. Private (RFC 1918) IPs only; they do **not** need to be
    registered via Put IP.
  - Shahkar rejects ranges that overlap. Only static IPs should be registered.
- **DedicatedServer / Colocation:** `province` is required on registration (the new DTOs enforce it).
- **1.2 update only:** the service is identified by `id` alone — no `serviceNumber`, no
  `dataCenterId` in `serviceUpdate`, and no `customerUpdate` (it no longer appears in the
  document). The update is also two-step, like registration.
- **9.4 update** is unchanged apart from the IP fields: `serviceNumber` + `dataCenterId` are
  still sent.

### Adding a future version

1. Add a case to [`ApiVersion`](src/Enums/ApiVersion.php) keyed by the new document's version number.
2. Add a contract + service implementing that document's flow.
3. Register the binding + a `match` arm in
   [`ShahkarDataCenterServiceProvider`](src/ShahkarDataCenterServiceProvider.php) and
   [`ShahkarDataCenterManager`](src/Support/ShahkarDataCenterManager.php).

---

# Version 9.4 — single request, no OTP, `publicIps` / `privateIps`

Everything in this section is reached through `ShahkarDataCenter::v94()`. The flow,
endpoints and person DTOs are the same as [9.2](#version-92--single-request-no-otp) (see
there for all person fields, foreign persons and legal persons). The difference is the
service data: use the `*V94DTO` classes, which send `publicIps` / `privateIps`.

### 9.4 — Register

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV92DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceV94DTO;

$person = new NaturalPersonV92DTO(
    identificationNo: '0987654321',
    name:             'علی',
    family:           'صارمی',
    mobile:           '09127613814',
    fatherName:       'یوسف',
    birthDate:        '13541101',
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
);

$service = new SharedWebHostingServiceV94DTO(
    dataCenterId: '34689999',
    publicIps:    '185.168.12.10-185.168.12.10', // required, public only
    bandwidth:    256,
    startDate:    '13991211',
    urlList:      'cra.ir',
    privateIps:   '192.168.1.1-192.168.1.10',    // optional, private only
    endDate:      '13991212',
    hasSSL:       true,
    hasIXP:       true,
);

$response = ShahkarDataCenter::v94()->registerForNaturalPerson($person, $address, $service);

// Legal person: same call shape with a LegalPersonV92DTO
ShahkarDataCenter::v94()->registerForLegalPerson($legal, $address, $service);
```

> Any service type works — see [Service DTOs — v9.4 / v1.2](#service-dtos--v94--v12-publicips--privateips)
> for VPS, Dedicated/Colocation and CDN.

### 9.4 — Update a service

One request, no OTP. `dataCenterId` is still required on the update DTO (it identifies the
service and can't be changed). Send only the other fields you want to change.

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Person\CustomerUpdateV92DTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateV94DTO;

ShahkarDataCenter::v94()->update(
    serviceId:      'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber:  '34689658',
    serviceUpdate:  new SharedWebHostingUpdateV94DTO(
        dataCenterId: '34689658',
        publicIps:    '185.168.12.10-185.168.12.10',
        privateIps:   '192.168.1.1-192.168.1.10',
        hasIXP:       true,
    ),
    addressUpdate:  new AddressUpdateDTO(townshipName: 'فیروزکوه'),     // optional
    customerUpdate: new CustomerUpdateV92DTO(email: 'moghadam@smp.com'), // optional
);
```

### 9.4 — Close / delete a service

Unchanged from 9.2:

```php
ShahkarDataCenter::v94()->close(serviceId: 'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM', serviceNumber: '54123');  // update + "close": 1
ShahkarDataCenter::v94()->delete(serviceId: 'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM', serviceNumber: '85231'); // delete endpoint
```

---

# Version 1.2 — two-step OTP, `publicIps` / `privateIps`

Everything in this section is reached through `ShahkarDataCenter::v12()`. Registration
works exactly like [1.0](#version-10--two-step-otp) (same person DTOs, same two steps);
use the `*V12DTO` service classes, which send `publicIps` / `privateIps`.

> - **Step 1:** send the request **without** OTP(s) → Shahkar sends the code(s).
> - **Step 2:** repeat the **same** call with the same `requestId`, with the OTP(s) filled in.

> Shahkar currently accepts this service only for **Iranian** natural and legal persons, so
> leave `nationality` / `agentNationality` empty.

### 1.2 — Register (two steps)

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;
use Shahkar\DataCenter\DTOs\Person\LegalPersonV1DTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV1DTO;
use Shahkar\DataCenter\DTOs\Service\VpsServiceV12DTO;

$service = new VpsServiceV12DTO(
    dataCenterId:      '34689999',
    centerName:        'شقایق',
    dataCenterAddress: 'تهران، خیابان شریعتی، ورودی شماره 17 وزارت ارتباطات',
    publicIps:         '185.168.12.10-185.168.12.10',
    bandwidth:         256,
    startDate:         '13991211',
    privateIps:        '192.168.1.1-192.168.1.10',
    endDate:           '13991212',
    province:          '021',
    hasIXP:            true,
);

// $address: an AddressDTO, as in the 9.4 example above

// Step 1 — no otp
$step1 = ShahkarDataCenter::v12()->registerForNaturalPerson(
    new NaturalPersonV1DTO(identificationNo: '0987654321'), $address, $service,
);

// Step 2 — same requestId, with the otp the subscriber received
$response = ShahkarDataCenter::v12()->registerForNaturalPerson(
    person:    new NaturalPersonV1DTO(identificationNo: '0987654321', otp: 12341),
    address:   $address,
    service:   $service,
    requestId: $step1->requestId,
);

```

**Legal person** — two OTPs on step 2 (company SIM + agent's primary SIM):

```php
$legal = fn (?int $otp = null, ?int $agentOtp = null) => new LegalPersonV1DTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',
    agentIdentificationNo: '0072314567',
    otp:                   $otp,
    agentOtp:              $agentOtp,
);

$legalStep1 = ShahkarDataCenter::v12()->registerForLegalPerson($legal(), $address, $service);

ShahkarDataCenter::v12()->registerForLegalPerson(
    person:    $legal(otp: 1234, agentOtp: 56781),
    address:   $address,
    service:   $service,
    requestId: $legalStep1->requestId,
);
```

### 1.2 — Update a service (two steps)

The service is identified by its `id` only: there is **no** `serviceNumber` argument, the
`*UpdateV12DTO` classes have **no** `dataCenterId`, and there is no `customerUpdate`. Like
registration, send it first without OTPs, then again with the same `requestId` and the codes.

**Natural person:**

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateV12DTO;

$update  = new SharedWebHostingUpdateV12DTO(
    publicIps:  '185.168.12.10-185.168.12.10',
    privateIps: '192.168.1.1-192.168.1.10',
    hasIXP:     true,
);
$address = new AddressUpdateDTO(townshipName: 'فیروزکوه', postalCode: '7654316543'); // optional

// Step 1 — no otp
$step1 = ShahkarDataCenter::v12()->updateForNaturalPerson(
    serviceId:     'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceUpdate: $update,
    addressUpdate: $address,
);

// Step 2 — with the otp
ShahkarDataCenter::v12()->updateForNaturalPerson(
    serviceId:     'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceUpdate: $update,
    addressUpdate: $address,
    otp:           12341,
    requestId:     $step1->requestId,
);
```

**Legal person** — same, with `otp` and `agentOtp` on step 2:

```php
use Shahkar\DataCenter\DTOs\Service\VpsUpdateV12DTO;

$serviceId = 'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8';
$update    = new VpsUpdateV12DTO(bandwidth: 512, publicIps: '185.168.12.10-185.168.12.10');

$legalStep1 = ShahkarDataCenter::v12()->updateForLegalPerson($serviceId, $update);

ShahkarDataCenter::v12()->updateForLegalPerson(
    serviceId:     $serviceId,
    serviceUpdate: $update,
    otp:           1234,
    agentOtp:      56781,
    requestId:     $legalStep1->requestId,
);
```

If only the address changes, pass an empty update DTO (e.g. `new VpsUpdateV12DTO()`); the
empty `serviceUpdate` is left out of the request.

### 1.2 — Close a service

Unchanged from 1.0:

```php
ShahkarDataCenter::v12()->close(serviceId: 'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM');
```

---

# Version 9.2 — single request, no OTP

> Superseded by [9.4](#version-94--single-request-no-otp-publicips--privateips), which splits
> `ips` into `publicIps` / `privateIps`. Kept for callers still pinned to the 9.2 document.

Everything in this section is reached through `ShahkarDataCenter::v92()`. Registration,
update, close and delete each complete in **one** request — there is no OTP step. The
full identity is sent inline via the V9.2 person DTOs.

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
    $response = ShahkarDataCenter::v92()
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

ShahkarDataCenter::v92()->registerForLegalPerson($legal, $address, $service);
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

ShahkarDataCenter::v92()->update(
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
ShahkarDataCenter::v92()->close(
    serviceId:     'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM',
    serviceNumber: '54123',
);
```

### 9.2 — Delete a service

Permanently deletes the service (its own endpoint).

```php
ShahkarDataCenter::v92()->delete(
    serviceId:     'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM',
    serviceNumber: '85231',
);
```

---

# Version 1.0 — two-step OTP

> Superseded by [1.2](#version-12--two-step-otp-publicips--privateips), which splits `ips`
> into `publicIps` / `privateIps` and identifies the service on update by `id` only. Kept
> for callers still pinned to the 1.0 document.

Everything in this section is reached through `ShahkarDataCenter::v1()`. Registration and
update are a **two-step** process:

> - **Step 1:** send the data **without** an OTP → Shahkar sends a one-time code to the subscriber.
> - **Step 2:** repeat the **same** call with the same `requestId`, this time with the OTP filled in → the service is finalized.

Unlike v9.2, you do **not** send full personal details — Shahkar resolves the identity
from `identificationNo` + the OTP.

### 1.0 — Register for a natural person (two steps)

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonV1DTO;
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
$person = new NaturalPersonV1DTO(
    identificationNo: '0987654321',
);

try {
    $response = ShahkarDataCenter::v1()
        ->registerForNaturalPerson($person, $address, $service);

    // Persist requestId — you MUST reuse it in step 2
    session(['shahkar_request_id' => $response->requestId]);
} catch (ShahkarApiException $e) {
    logger()->error('Shahkar step 1 error', ['message' => $e->getMessage()]);
}

// ---------- Step 2: same call WITH the received otp ----------
$personWithOtp = new NaturalPersonV1DTO(
    identificationNo: '0987654321',
    otp:              12341, // code the subscriber received
);

$response = ShahkarDataCenter::v1()->registerForNaturalPerson(
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
use Shahkar\DataCenter\DTOs\Person\LegalPersonV1DTO;

// ---------- Step 1: WITHOUT otp/agentOtp ----------
$person = new LegalPersonV1DTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',   // legal person's mobile
    agentIdentificationNo: '0072314567',    // agent's national code
);

ShahkarDataCenter::v1()->registerForLegalPerson($person, $address, $service);

// ---------- Step 2: WITH both OTPs ----------
$personWithOtp = new LegalPersonV1DTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',
    agentIdentificationNo: '0072314567',
    otp:                   1234,    // OTP sent to the legal person's mobile
    agentOtp:              56781,   // OTP sent to the agent's primary SIM
);

$response = ShahkarDataCenter::v1()->registerForLegalPerson($personWithOtp, $address, $service);
```

> The `$service` can be any service type — see [Service DTOs (shared)](#service-dtos-shared).

### 1.0 — Update a service

The OTP is passed directly on the update call (no separate step 1).

**Natural person:**

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;

$response = ShahkarDataCenter::v1()->updateForNaturalPerson(
    serviceId:     'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber: '34689658',
    otp:           12341,
    serviceUpdate: new SharedWebHostingUpdateDTO('DC001', ips: '185.168.12.11-185.168.12.11', hasIXP: true),
    addressUpdate: new AddressUpdateDTO(townshipName: 'Firuzkuh', postalCode: '7654316543'), // optional
);
```

**Legal person** (two OTPs + optional `customerUpdate`):

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonUpdateV1DTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateDTO;

$response = ShahkarDataCenter::v1()->updateForLegalPerson(
    serviceId:      'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber:  '34689658',
    otp:            1234,
    agentOtp:       56781,
    serviceUpdate:  new VpsUpdateDTO('DC001', bandwidth: 512, ips: '185.168.12.11-185.168.12.11'),
    customerUpdate: new LegalPersonUpdateV1DTO(agentIdentificationNo: '0063222313'), // optional
);
```

### 1.0 — Close a service

```php
try {
    $response = ShahkarDataCenter::v1()->close(
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

# IP Registration service (`putIP`) — `ShahkarIp`

A **separate** Shahkar service (document **v1.5**), independent of the Data Center web
service. Operators use it to declare the IP ranges they advertise; Shahkar then validates
Data Center / end-user IPs against these registrations and rejects ranges that overlap with
another operator's. It has its own facade, `ShahkarIp` — nothing here goes through
`ShahkarDataCenter`.

It shares the same connection config (`base_url`, credentials, `operator_id`); no extra
setup is required.

Each IP list is a comma-joined string of `start-end` ranges. A **single IP** is written as
`ip-ip` (start == end). You may pass a ready string or an **array** of ranges (formatted for
you via `IpRangeHelper`).

### Register IPs — `put()`

Sends the operator's full list (end-user, data-center and other-operator IPs). This replaces
any previously registered list. On success the response carries a tracking `id`.

```php
use Shahkar\DataCenter\Facades\ShahkarIp;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;

try {
    $response = ShahkarIp::put(
        endUsersIPs:       ['66.171.248.170-66.171.248.215', '64.20.21.2-64.20.21.2'],
        dataCentersIPs:    ['71.151.48.16-71.151.48.30', '150.0.0.2-150.0.0.5'],
        otherOperatorsIPs: ['192.168.14.21-192.168.14.30'],
    );

    if ($response->success) {
        $trackingId = $response->get('id'); // keep for follow-ups
    }
} catch (ShahkarApiException $e) {
    // 340 => input ranges overlap each other
    // 341 => input ranges overlap IPs already registered by other operators
    // the conflicting range is returned in the response body's "details" field
    logger()->warning('putIP failed', ['message' => $e->getMessage(), 'body' => $e->getResponseBody()]);
}
```

Strings are accepted too, if you already have them formatted:

```php
ShahkarIp::put(
    endUsersIPs:       '66.171.248.170-66.171.248.215,64.20.21.2-64.20.21.2',
    dataCentersIPs:    '71.151.48.16-71.151.48.30',
    otherOperatorsIPs: '192.168.14.21-192.168.14.30',
);
```

### View registered IPs — `fetch()`

Returns everything currently registered for this operator.

```php
$response = ShahkarIp::fetch();

$endUsers  = $response->get('endUsersIPs');       // e.g. "150.0.1.0-150.0.1.120"
$dataCent  = $response->get('dataCentersIPs');     // e.g. "150.0.0.1-150.0.0.255,210.0.0.1-210.0.0.200"
$others    = $response->get('otherOperatorsIPs');
```

### Delete all IPs — `truncate()`

Removes **all** IPs registered for this operator.

```php
$response = ShahkarIp::truncate();
```

> **Tip:** validate ranges locally before sending with
> [`IpRangeHelper::validate()`](#using-iprangehelper) to catch obvious overlaps/format errors
> without a round-trip.

---

# Estelaam identity-inquiry service — `ShahkarInquiry`

Another **separate** Shahkar service (document **v1.4**), independent of the Data Center web
service. It verifies a person's identity — and optionally their postal address — against
Shahkar's reference registry. One endpoint (`rest/shahkar/estelaam`) serves all person types;
there is no OTP. It has its own facade, `ShahkarInquiry`, and shares only the connection
config (`base_url`, credentials, `operator_id`).

> **Reading the result:** the outcome is in the **response body**, not the HTTP status. On a
> reachable request you get HTTP 200 either way; check `response`/`result`:
> `response == 200` / `"OK."` when verified, `610` / `"CustomerNotFoundException"` when not.
> Use `$response->get('response')`, **not** `$response->success`.

Address is optional. If you send any address field, Shahkar requires the rest of the address
block too (`postalCode`, `provinceCode`, `townshipName`, `address`, `street2`, `houseNumber`;
`tel` optional). The shared `AddressDTO` produces exactly that shape.

### Verify a natural person

```php
use Shahkar\DataCenter\Facades\ShahkarInquiry;
use Shahkar\DataCenter\DTOs\Inquiry\NaturalPersonInquiryDTO;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\Enums\InquiryIdentificationType;

// ---- Iranian (identificationType defaults to NationalCode = 0) ----
$response = ShahkarInquiry::verifyNaturalPerson(
    person: new NaturalPersonInquiryDTO(
        identificationNo: '0987654321',
        name:             'علی',
        family:           'صارمی',
        birthDate:        '13541101',   // Jalali for Iranian
        fatherName:       'یوسف',
        certificateNo:    '10984',      // send "0" if the person has none
        gender:           1,            // optional: 1 = male, 2 = female
    ),
    address: new AddressDTO(            // optional
        provinceCode: '021',
        address:      'خیابان مطهری، کوچه شهید تیموری، پلاک 272، طبقه ۵، واحد 10',
        houseNumber:  '272',
        postalCode:   '1576653133',
        townshipName: 'تهران',
        street2:      'شهید تیموری',
        tel:          '02122334455',
    ),
    serviceType: 39,                    // optional service block
);

if ($response->get('response') === 200) {
    // identity verified
} elseif ($response->get('result') === 'CustomerNotFoundException') {
    // not found in the reference registry
}
```

**Foreign natural person:** choose the document type (`Passport`, `AmayeshCard`, `RefugeeCard`
or `IdentityCard`), send a Gregorian `birthDate` and `nationality`; `universalNo` is optional:

```php
ShahkarInquiry::verifyNaturalPerson(
    new NaturalPersonInquiryDTO(
        identificationNo:   'P45887457',
        name:               'JOHN',
        family:             'HOPKINS',
        birthDate:          '19830813',                       // Gregorian
        identificationType: InquiryIdentificationType::Passport,
        fatherName:         'GEORGE',
        nationality:        'USA',
        universalNo:        '154263652',                      // optional
    ),
);
```

### Verify a legal person

No agent data is required (unlike the Data Center flow).

```php
use Shahkar\DataCenter\DTOs\Inquiry\LegalPersonInquiryDTO;
use Shahkar\DataCenter\Enums\InquiryIdentificationType;

// ---- Iranian company (identificationType defaults to NationalId = 5) ----
ShahkarInquiry::verifyLegalPerson(
    new LegalPersonInquiryDTO(
        identificationNo: '56235625365263',
        companyName:      'آریا مهر تجارت نوین',
        companyType:      1,
        registrationDate: '13940424',
        registrationNo:   '475771',
    ),
);

// ---- Foreign company: identificationType = FidaId (6) ----
ShahkarInquiry::verifyLegalPerson(
    new LegalPersonInquiryDTO(
        identificationNo:   '56235625365263',
        companyName:        'Benz',
        companyType:        1,
        registrationDate:   '13940424',
        registrationNo:     '475771',
        identificationType: InquiryIdentificationType::FidaId,
    ),
);
```

### Identity document types

`InquiryIdentificationType` covers every type this service accepts:

| Case           | Value | Applies to             |
|----------------|-------|------------------------|
| `NationalCode` | 0     | natural, Iranian       |
| `Passport`     | 1     | natural, foreign       |
| `AmayeshCard`  | 2     | natural, foreign       |
| `RefugeeCard`  | 3     | natural, foreign       |
| `IdentityCard` | 4     | natural, foreign       |
| `NationalId`   | 5     | legal, Iranian         |
| `FidaId`       | 6     | legal, foreign         |

---

# Reseller Code service — `ShahkarReseller`

Another **separate** Shahkar service (document **v9.4**, service **type 30**), independent of
the Data Center web service. Operators use it to register a reseller's sales code, then update,
transfer, close or delete it. It is single-step with no OTP, via its own `ShahkarReseller`
facade. It shares the `rest/shahkar/{put,update,delete}` endpoints with other Shahkar services;
Shahkar tells them apart by `service.type = 30`.

**Two reseller codes, don't confuse them:**
- The code you pass in `ResellerServiceDTO` is the code **being registered** — it becomes the
  service's `serviceNumber` for later update/transfer/close/delete.
- The top-level `resellerCode` (the requesting operator's own code) is taken from
  `config('shahkar-datacenter.reseller_code')` and sent automatically.

### Register

```php
use Shahkar\DataCenter\Facades\ShahkarReseller;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Reseller\NaturalPersonResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\LegalPersonResellerDTO;
use Shahkar\DataCenter\DTOs\Reseller\ResellerServiceDTO;

// ---- Natural person (Iranian) ----
$response = ShahkarReseller::registerForNaturalPerson(
    person: new NaturalPersonResellerDTO(
        identificationNo: '0012345678',
        name:             'سیدمحمد',
        family:           'حسینی',
        mobile:           '09121234567',
        // iranian: true (default) -> identificationType 0
        fatherName:       'جمشید',
        birthDate:        '13671201',
        birthPlace:       'تهران',
        certificateNo:    '12345',
        gender:           1,
        email:            'test@email.com',
    ),
    service: new ResellerServiceDTO(
        resellerCode: '9896',        // the code being registered (becomes serviceNumber)
        province:     '021',
        ipStatic:     true,          // when true, rangeIps is required
        rangeIps:     '100.100.100.20-100.100.100.30,100.100.100.40-100.100.100.50',
    ),
    address: new AddressDTO(         // optional
        provinceCode: '021',
        address:      'خیابان مطهری، کوچه شهید احمدعلی نیری، پلاک 18',
        houseNumber:  '18',
        postalCode:   '1345676543',
        townshipName: 'اسلامشهر',
        street2:      'کوچه شهید احمدعلی نیری',
        tel:          '02122334455',
    ),
);

// ---- Legal person (foreign company; agent may still be Iranian) ----
ShahkarReseller::registerForLegalPerson(
    person: new LegalPersonResellerDTO(
        identificationNo:      '357812321',
        mobile:                '09121234567',
        companyName:           'Apple',
        agentIdentificationNo: '0012345678',
        iranian:               false,          // company -> identificationType 6
        agentIranian:          true,           // agent   -> agentIdentificationType 0
        nationality:           'USA',
        companyType:           4,
        registrationDate:      '20201205',
        registrationNo:        '223344',
        agentFirstName:        'سیدمحمد',
        agentLastName:         'حسینی',
        agentNationality:      'IRN',
        agentBirthDate:        '13691201',
        agentBirthCertificateNo: '12345',
        agentMobile:           '09121234567',
    ),
    service: new ResellerServiceDTO('9896', '021'),
);
```

### Update

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Reseller\ResellerServiceUpdateDTO;
use Shahkar\DataCenter\DTOs\Reseller\CustomerUpdateResellerDTO;

ShahkarReseller::update(
    serviceId:      'jIdpWTbUBxoYThNn4a9g3zA088c5LgujZLBg4vm-rYs',
    serviceNumber:  '9896',   // the reseller code assigned at registration
    serviceUpdate:  new ResellerServiceUpdateDTO(ipStatic: true, rangeIps: '100.100.100.20-100.100.100.30'),
    addressUpdate:  new AddressUpdateDTO(townshipName: 'فیروزکوه', postalCode: '7654316543'), // optional
    customerUpdate: new CustomerUpdateResellerDTO(name: 'ساناز', email: 'sample@smp.com'),     // optional
);
```

### Transfer to a new owner

Moves the service to a new person; pass a full person DTO (and optionally their address).

```php
ShahkarReseller::transferToNaturalPerson(
    serviceId:     'WZOzs3PX2rKTg4q-TH3W3YQI8a3pliprH-DGI9KGIz8',
    serviceNumber: '9896',
    person: new NaturalPersonResellerDTO(
        identificationNo: '0031245698',
        name:             'زهرا',
        family:           'علوی',
        mobile:           '09127654321',
        fatherName:       'تقی',
        birthDate:        '13750211',
        certificateNo:    '6789',
        gender:           2,
    ),
);

// ShahkarReseller::transferToLegalPerson(...) works the same with a LegalPersonResellerDTO.
```

### Close and delete

```php
ShahkarReseller::close(serviceId: 'tw_VAEQOp7ri...', serviceNumber: '9896');  // update + "close": 1
ShahkarReseller::delete(serviceId: 'tw_VAEQOp7ri...', serviceNumber: '9896'); // delete endpoint
```

---

## Service DTOs — v9.4 / v1.2 (`publicIps` / `privateIps`)

Use the `V94` classes with `v94()` and the `V12` classes with `v12()`. The registration
DTOs take the same arguments in both versions; the update DTOs differ only in that the
V9.4 ones start with a required `dataCenterId` and the V1.2 ones have none.

| Service type             | `dataCenterType` | Register DTO (v9.4 / v1.2)                                             | Update DTO (v9.4 / v1.2)                                             |
|--------------------------|------------------|------------------------------------------------------------------------|----------------------------------------------------------------------|
| SharedWebHosting         | 14               | `SharedWebHostingServiceV94DTO` / `SharedWebHostingServiceV12DTO`       | `SharedWebHostingUpdateV94DTO` / `SharedWebHostingUpdateV12DTO`       |
| VPS                      | 11               | `VpsServiceV94DTO` / `VpsServiceV12DTO`                                 | `VpsUpdateV94DTO` / `VpsUpdateV12DTO`                                 |
| DedicatedServer / Colocation | 12 / 13      | `DedicatedColocationServiceV94DTO` / `DedicatedColocationServiceV12DTO` | `DedicatedColocationUpdateV94DTO` / `DedicatedColocationUpdateV12DTO` |
| CDN                      | 15               | `CdnServiceV94DTO` / `CdnServiceV12DTO`                                 | `CdnUpdateV94DTO` / `CdnUpdateV12DTO`                                 |

Each IP field is a comma-joined string of `start-end` ranges (a single IP is `ip-ip`); build
it with [`IpRangeHelper::format()`](#using-iprangehelper) if you hold an array.

```php
use Shahkar\DataCenter\DTOs\Service\CdnServiceV94DTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceV12DTO;
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationUpdateV94DTO;
use Shahkar\DataCenter\DTOs\Service\VpsUpdateV12DTO;
use Shahkar\DataCenter\Enums\DataCenterType;

// SharedWebHosting / VPS: shown in the 9.4 and 1.2 sections above.

$cdn = new CdnServiceV94DTO(
    dataCenterId: '34689999',
    publicIps:    '185.168.12.10-185.168.12.10',
    bandwidth:    256,
    startDate:    '13991211',
    urlList:      'cra.ir',
    privateIps:   '192.168.1.1-192.168.1.10',
    endDate:      '13991212',
    hasSSL:       true,
);

$dedicated = new DedicatedColocationServiceV12DTO(
    dataCenterId:      '34689999',
    centerName:        'Shaghayegh',
    dataCenterAddress: 'Tehran, Shariati St., Entrance No. 17, Ministry of Communications',
    province:          '021',                         // required for this type
    publicIps:         '185.168.12.10-185.168.12.10',
    bandwidth:         256,
    startDate:         '13991211',
    lat:               '35.689198',
    lon:               '51.388973',
    rowIndex:          1,
    racIndex:          1,
    unitIndex:         1,
    dataCenterType:    DataCenterType::DedicatedServer, // or DataCenterType::Colocation
    privateIps:        '192.168.1.1-192.168.1.10',
    endDate:           '13991212',
    hasIXP:            true,
    units:             4,
);

// v9.4 update: dataCenterId first
$dedicatedUpdate94 = new DedicatedColocationUpdateV94DTO('34689999', rowIndex: 10, racIndex: 3, unitIndex: 12);

// v1.2 update: no dataCenterId
$vpsUpdate12 = new VpsUpdateV12DTO(bandwidth: 512, privateIps: '192.168.1.1-192.168.1.10');
```

---

## Service DTOs (shared)

These are the **`ips`-based** service DTOs used by the older **v9.2** and **v1.0** flows
(`v94()` / `v12()` reject them). They are the same for both `v92()` and `v1()`. Build the
`$service` you need and pass it to that version's `register...` call, or the `...UpdateDTO`
to its `update...` call.

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
    $response = ShahkarDataCenter::v94()->registerForNaturalPerson(/* ... */);
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
use Shahkar\DataCenter\Contracts\DataCenterApiV94Interface;   // v9.4
use Shahkar\DataCenter\Contracts\DataCenterApiV12Interface;   // v1.2
use Shahkar\DataCenter\Support\ShahkarDataCenterManager;    // any version, chosen at runtime
// (v9.2 / v1.0: DataCenterApiV92Interface / DataCenterApiV1Interface)

class RegistersDataCenters
{
    public function __construct(
        private readonly DataCenterApiV94Interface $v94,
        private readonly DataCenterApiV12Interface $v12,     // the v1.2 OTP flow
        private readonly ShahkarDataCenterManager  $shahkar,
    ) {}

    public function example(): void
    {
        // Directly against v9.4
        $this->v94->registerForNaturalPerson(/* NaturalPersonV92DTO */, $address, $serviceV94);

        // Or resolve a version dynamically
        $this->shahkar->v12()->registerForNaturalPerson(/* NaturalPersonV1DTO */, $address, $serviceV12);
    }
}
```

---

## Mocking in tests

Mock the contract for whichever version your code uses:

```php
use Shahkar\DataCenter\Contracts\DataCenterApiV94Interface;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

$this->mock(DataCenterApiV94Interface::class, function ($mock) {
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

For the other versions, mock `DataCenterApiV12Interface`, `DataCenterApiV92Interface` or `DataCenterApiV1Interface` instead.

---

## Important Notes

Applies to the **Data Center** service unless noted otherwise:

- **v9.4 / v1.2:** `publicIps` (required) may hold only public IPs, each range inside one the
  operator registered via Put IP (`ShahkarIp::put()`) or received from an assignable data
  center. `privateIps` (optional) may hold only private IPs, which need no Put IP registration.
  Register static IPs only; overlapping ranges are rejected.
- **v9.2 / v1.0:** all IPs in `ips` must be **public** and previously registered in Shahkar.
- **v1.2 / v1.0:** currently only **Iranian** natural and legal persons can be registered.
- For **natural** persons: an active primary SIM registered in their own name is mandatory.
- For **legal** persons: both a SIM registered to the legal person and the agent's primary SIM are required.
- The Data Center service cannot be registered for people **under 18 years old**.
- Data Center service **transfer** is not supported (the **Reseller Code** service *does* support transfer — see its section).
- Dates must be **Jalali (Solar Hijri)** in `YYYYMMDD` format (e.g. `14030101`).
- The province must be sent as its **numeric code** (e.g. `021` for Tehran, not the province name).
- **Data Center v9.4 / v9.2 & the Reseller Code service:** the operator's `resellerCode` (config `reseller_code`) is sent on every request; the v1.2 / v1.0 OTP flows do not use it.

---

## Package Architecture

```
src/
├── Contracts/              # Interfaces (Dependency Inversion principle)
│   ├── DataCenterApiV94Interface.php   # v9.4 flow ('9.4')
│   ├── DataCenterApiV12Interface.php   # v1.2 OTP flow ('1.2')
│   ├── DataCenterApiV92Interface.php   # v9.2 flow ('9.2')
│   ├── DataCenterApiV1Interface.php    # v1.0 OTP flow ('1.0')
│   ├── IpRegistrationApiInterface.php  # standalone putIP service (v1.5)
│   ├── InquiryApiInterface.php         # standalone estelaam service (v1.4)
│   ├── ResellerApiInterface.php        # standalone reseller service (v9.4)
│   ├── HttpClientInterface.php
│   ├── ServiceDataInterface.php
│   ├── ServiceDataV94Interface.php     # marker: service DTOs v94() accepts
│   └── ServiceDataV12Interface.php     # marker: service DTOs v12() accepts
├── DTOs/                   # Data Transfer Objects (type-safe)
│   ├── Address/
│   │   ├── AddressDTO.php               (shared)
│   │   └── AddressUpdateDTO.php         (shared)
│   ├── Inquiry/                         # estelaam service
│   │   ├── NaturalPersonInquiryDTO.php
│   │   └── LegalPersonInquiryDTO.php
│   ├── Reseller/                        # reseller code service
│   │   ├── NaturalPersonResellerDTO.php
│   │   ├── LegalPersonResellerDTO.php
│   │   ├── ResellerServiceDTO.php
│   │   ├── ResellerServiceUpdateDTO.php
│   │   └── CustomerUpdateResellerDTO.php
│   ├── Person/
│   │   ├── NaturalPersonV1DTO.php         (natural person — v1.0 & v1.2 OTP)
│   │   ├── LegalPersonV1DTO.php           (legal person — v1.0 & v1.2 OTP)
│   │   ├── LegalPersonUpdateV1DTO.php     (v1.0)
│   │   ├── NaturalPersonV92DTO.php      (natural person — v9.2 & v9.4)
│   │   ├── LegalPersonV92DTO.php        (legal person — v9.2 & v9.4)
│   │   └── CustomerUpdateV92DTO.php     (customer update — v9.2 & v9.4)
│   └── Service/
│       ├── Base/                        # register fields shared by the V94/V12 DTOs
│       ├── {SharedWebHosting,Vps,DedicatedColocation,Cdn}ServiceV94DTO.php   # v9.4
│       ├── {SharedWebHosting,Vps,DedicatedColocation,Cdn}UpdateV94DTO.php    # v9.4
│       ├── {SharedWebHosting,Vps,DedicatedColocation,Cdn}ServiceV12DTO.php   # v1.2
│       ├── {SharedWebHosting,Vps,DedicatedColocation,Cdn}UpdateV12DTO.php    # v1.2
│       ├── SharedWebHostingServiceDTO.php   # unsuffixed: ips-based, v9.2 & v1.0
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
│   ├── InquiryIdentificationType.php   # estelaam service
│   └── ServiceType.php
├── Exceptions/
│   ├── ShahkarApiException.php
│   ├── OtpRequiredException.php
│   └── ShahkarValidationException.php
├── Http/
│   ├── ShahkarHttpClient.php
│   └── Responses/ApiResponse.php
├── Services/
│   ├── DataCenterApiServiceV94.php     # v9.4 flow ('9.4')
│   ├── DataCenterApiServiceV12.php     # v1.2 OTP flow ('1.2')
│   ├── DataCenterApiServiceV92.php     # v9.2 flow ('9.2')
│   ├── DataCenterApiServiceV1.php      # v1.0 OTP flow ('1.0')
│   ├── IpRegistrationApiService.php    # standalone putIP service (v1.5)
│   ├── InquiryApiService.php           # standalone estelaam service (v1.4)
│   └── ResellerApiService.php          # standalone reseller service (v9.4)
├── Support/
│   ├── ShahkarDataCenterManager.php    # resolves the version to use
│   ├── RequestIdGenerator.php
│   └── IpRangeHelper.php
└── Facades/
    ├── ShahkarDataCenter.php
    ├── ShahkarIp.php                   # facade for the putIP service
    ├── ShahkarInquiry.php              # facade for the estelaam service
    └── ShahkarReseller.php             # facade for the reseller code service
```

---

## Running the Tests

```bash
cd vendor/shahkar/datacenter
composer install
./vendor/bin/phpunit
```
