# مستندات کتابخانه `shahkar/datacenter`

راهنمای کامل استفاده از پکیج Laravel برای سرویس مرکز داده کاربر نهایی شاهکار

---

## نصب و راه‌اندازی

### ۱. نصب پکیج

```bash
composer require shahkar/datacenter
```

### ۲. انتشار فایل کانفیگ

```bash
php artisan vendor:publish --tag=shahkar-datacenter-config
```

این دستور فایل `config/shahkar-datacenter.php` را در پروژه شما ایجاد می‌کند.

### ۳. تنظیم متغیرهای محیطی

مقادیر زیر را به فایل `.env` اضافه کنید:

```env
SHAHKAR_BASE_URL=https://api.shahkar.ir
SHAHKAR_USERNAME=your_username
SHAHKAR_PASSWORD=your_password
SHAHKAR_OPERATOR_ID=013
SHAHKAR_TIMEOUT=30
SHAHKAR_VERIFY_SSL=true
```

---

## معماری پکیج

```
src/
├── Contracts/              # Interface ها (اصل Dependency Inversion)
│   ├── DataCenterApiInterface.php
│   ├── HttpClientInterface.php
│   └── ServiceDataInterface.php
├── DTOs/                   # Data Transfer Objects (ایمن از نوع)
│   ├── Address/
│   │   ├── AddressDTO.php
│   │   └── AddressUpdateDTO.php
│   ├── Person/
│   │   ├── NaturalPersonDTO.php   (حقیقی)
│   │   ├── LegalPersonDTO.php     (حقوقی)
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

## فرآیند ثبت سرویس (دو مرحله‌ای)

> **نکته مهم:** ثبت و به‌روزرسانی سرویس یک فرآیند **دو مرحله‌ای** است:
> - **مرحله اول:** ارسال اطلاعات بدون OTP → سیستم کد یکبار مصرف ارسال می‌کند
> - **مرحله دوم:** ارسال مجدد همان اطلاعات + کد OTP دریافت‌شده → ثبت نهایی

---

## نمونه‌های کد

### ثبت SharedWebHosting برای شخص حقیقی

```php
use Shahkar\DataCenter\Facades\ShahkarDataCenter;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Person\NaturalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingServiceDTO;
use Shahkar\DataCenter\Exceptions\ShahkarApiException;

// ---------- مرحله اول: ارسال بدون OTP ----------
$person = new NaturalPersonDTO(
    identificationNo: '0987654321',
);

$address = new AddressDTO(
    provinceCode: '021',
    address:      'خیابان مطهری، کوچه شهید احمدعلی نیری، پالک ۱۸ طبقه ۵ واحد ۱۰',
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
    $response = ShahkarDataCenter::registerForNaturalPerson($person, $address, $service);
    // ذخیره requestId برای مرحله دوم
    session(['shahkar_request_id' => $response->requestId]);
} catch (ShahkarApiException $e) {
    // خطای API
    logger()->error('Shahkar API error', ['message' => $e->getMessage(), 'body' => $e->getResponseBody()]);
}


// ---------- مرحله دوم: ارسال با OTP ----------
$personWithOtp = new NaturalPersonDTO(
    identificationNo: '0987654321',
    otp:              12341, // کد دریافت‌شده توسط مشترک
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
        echo "سرویس با موفقیت ثبت شد. شماره سرویس: {$serviceNumber}";
    }
} catch (ShahkarApiException $e) {
    logger()->error('Shahkar OTP error', ['message' => $e->getMessage()]);
}
```

---

### ثبت VPS برای شخص حقوقی

```php
use Shahkar\DataCenter\DTOs\Person\LegalPersonDTO;
use Shahkar\DataCenter\DTOs\Service\VpsServiceDTO;

// ---------- مرحله اول ----------
$person = new LegalPersonDTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',   // موبایل شخص حقوقی
    agentIdentificationNo: '0072314567',    // کد ملی نماینده
);

$service = new VpsServiceDTO(
    dataCenterId:       '34689999',
    centerName:         'شقایق',
    dataCenterAddress:  'تهران، خیابان شریعتی، ورودی شماره ۱۷ وزارت ارتباطات',
    ips:                '185.168.12.10-185.168.12.10',
    bandwidth:          256,
    startDate:          '13991211',
    endDate:            '13991212',
    province:           '021',
    hasIXP:             true,
    urlList:            'cra.ir',
);

ShahkarDataCenter::registerForLegalPerson($person, $address, $service);


// ---------- مرحله دوم (با دو OTP) ----------
$personWithOtp = new LegalPersonDTO(
    identificationNo:      '33273340437',
    mobileNumber:          '09128964532',
    agentIdentificationNo: '0072314567',
    otp:                   1234,    // OTP ارسال‌شده به موبایل شخص حقوقی
    agentOtp:              56781,   // OTP ارسال‌شده به سیم‌کارت اصلی نماینده
);

$response = ShahkarDataCenter::registerForLegalPerson($personWithOtp, $address, $service);
```

---

### ثبت DedicatedServer / Colocation

```php
use Shahkar\DataCenter\DTOs\Service\DedicatedColocationServiceDTO;
use Shahkar\DataCenter\Enums\DataCenterType;

$service = new DedicatedColocationServiceDTO(
    dataCenterId:      '34689999',
    centerName:        'شقایق',
    dataCenterAddress: 'تهران، خیابان شریعتی، ورودی شماره ۱۷ وزارت ارتباطات',
    ips:               '185.168.12.10-185.168.12.10',
    bandwidth:         256,
    startDate:         '13991211',
    lat:               '35.689198',
    lon:               '51.388973',
    rowIndex:          1,
    racIndex:          1,
    unitIndex:         1,
    dataCenterType:    DataCenterType::DedicatedServer, // یا DataCenterType::Colocation
    endDate:           '13991212',
    province:          '021',
    hasIXP:            true,
    units:             4,
);

ShahkarDataCenter::registerForNaturalPerson($person, $address, $service);
```

---

### ثبت CDN

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

### به‌روزرسانی سرویس

#### SharedWebHosting — شخص حقیقی

```php
use Shahkar\DataCenter\DTOs\Address\AddressUpdateDTO;
use Shahkar\DataCenter\DTOs\Service\SharedWebHostingUpdateDTO;

$addressUpdate = new AddressUpdateDTO(
    townshipName: 'فیروزکوه',
    address:      'خیابان امام، کوچه شهید مهدوی، پالک ۸ طبقه ۱ واحد ۲',
    street2:      'کوچه شهید مهدوی',
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

#### VPS — شخص حقوقی

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

#### DedicatedServer/Colocation — به‌روزرسانی

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

#### CDN — به‌روزرسانی

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

### بستن سرویس

```php
try {
    $response = ShahkarDataCenter::close(
        serviceId: 'tw_VAEQOp7riqioo6D9Dec-tvHjlKDtebqTt9QgK0GM'
    );

    if ($response->success) {
        echo 'سرویس با موفقیت بسته شد.';
    }
} catch (ShahkarApiException $e) {
    logger()->error('Close failed', ['error' => $e->getMessage()]);
}
```

---

## استفاده از IpRangeHelper

```php
use Shahkar\DataCenter\Support\IpRangeHelper;

// اعتبارسنجی بازه‌ها قبل از ارسال
$ranges = [
    '185.168.1.1-185.168.1.250',
    '185.168.2.1-185.168.2.100',
];

try {
    IpRangeHelper::validate($ranges); // بدون exception یعنی معتبر است
} catch (\InvalidArgumentException $e) {
    echo 'خطا در بازه IP: ' . $e->getMessage();
}

// تبدیل آرایه به فرمت API
$ipsString = IpRangeHelper::format($ranges);
// نتیجه: "185.168.1.1-185.168.1.250,185.168.2.1-185.168.2.100"
```

---

## مدیریت خطاها

```php
use Shahkar\DataCenter\Exceptions\ShahkarApiException;
use Shahkar\DataCenter\Exceptions\ShahkarValidationException;
use Shahkar\DataCenter\Exceptions\OtpRequiredException;

try {
    $response = ShahkarDataCenter::registerForNaturalPerson(...);
} catch (ShahkarValidationException $e) {
    // خطای ۴۲۲ - اطلاعات نادرست
    $errors = $e->getResponseBody();
    logger()->warning('Validation failed', $errors ?? []);

} catch (OtpRequiredException $e) {
    // نیاز به OTP - درخواست را با OTP مجددا ارسال کنید
    $requestId = $e->getRequestId();
    session(['pending_request_id' => $requestId]);

} catch (ShahkarApiException $e) {
    // سایر خطاهای API
    logger()->error('Shahkar error', [
        'code'    => $e->getCode(),
        'message' => $e->getMessage(),
        'body'    => $e->getResponseBody(),
    ]);
}
```

---

## Dependency Injection (بدون Facade)

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
            address: new AddressDTO('021', 'خیابان آزادی', '10', '1234567890'),
            service: new SharedWebHostingServiceDTO('DC001', '1.2.3.4-1.2.3.4', 256, '14030101', 'cra.ir'),
        );
    }
}
```

---

## Mock در تست‌ها

```php
use Shahkar\DataCenter\Contracts\DataCenterApiInterface;
use Shahkar\DataCenter\Http\Responses\ApiResponse;

// در TestCase
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

## جدول انواع دیتاسنتر

| نوع سرویس | مقدار `dataCenterType` | DTO ثبت | DTO به‌روزرسانی |
|-----------|----------------------|---------|----------------|
| SharedWebHosting | 14 | `SharedWebHostingServiceDTO` | `SharedWebHostingUpdateDTO` |
| VPS | 11 | `VpsServiceDTO` | `VpsUpdateDTO` |
| DedicatedServer | 12 | `DedicatedColocationServiceDTO` | `DedicatedColocationUpdateDTO` |
| Colocation | 13 | `DedicatedColocationServiceDTO` | `DedicatedColocationUpdateDTO` |
| CDN | 15 | `CdnServiceDTO` | `CdnUpdateDTO` |

---

## نکات مهم

- تمام IPها باید از نوع **عمومی (public)** باشند و قبلاً در شاهکار ثبت شده باشند.
- برای اشخاص **حقیقی**: داشتن سیم‌کارت اصلی فعال به نام خودشان اجباری است.
- برای اشخاص **حقوقی**: هم سیم‌کارت ثبت‌شده به نام شخص حقوقی و هم سیم‌کارت اصلی نماینده لازم است.
- این سرویس برای افراد **زیر ۱۸ سال** قابل ثبت نیست.
- در حال حاضر امکان انتقال (Transfer) سرویس وجود ندارد.
- فرمت تاریخ‌ها باید **شمسی** به صورت `YYYYMMDD` باشد (مثلاً `14030101`).
- کد استان باید به صورت **کد عددی** ارسال شود (مثلاً `021` برای تهران، نه نام استان).

---

## اجرای تست‌ها

```bash
cd vendor/shahkar/datacenter
composer install
./vendor/bin/phpunit
```
