<?php

namespace Shahkar\DataCenter\Tests\Feature;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shahkar\DataCenter\Contracts\HttpClientInterface;
use Shahkar\DataCenter\DTOs\Address\AddressDTO;
use Shahkar\DataCenter\DTOs\Inquiry\LegalPersonInquiryDTO;
use Shahkar\DataCenter\DTOs\Inquiry\NaturalPersonInquiryDTO;
use Shahkar\DataCenter\Enums\InquiryIdentificationType;
use Shahkar\DataCenter\Http\Responses\ApiResponse;
use Shahkar\DataCenter\Services\InquiryApiService;

class InquiryApiServiceTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private InquiryApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->service    = new InquiryApiService($this->httpClient, '013');
    }

    private function makeResponse(int $code = 200, string $result = 'OK.'): ApiResponse
    {
        return new ApiResponse(
            success:    true,
            statusCode: 200,
            body:       ['result' => $result, 'response' => $code, 'id' => null],
            requestId:  'test-request-id',
        );
    }

    public function test_verify_natural_iranian_sends_expected_payload(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/estelaam',
                $this->callback(function (array $payload) {
                    return array_key_exists('requestId', $payload)
                        && $payload['estelaamType'] === 0
                        && $payload['identificationType'] === 0
                        && $payload['identificationNo'] === '0987654321'
                        && $payload['name'] === 'علی'
                        && $payload['certificateNo'] === '10984'
                        && !array_key_exists('address', $payload)
                        && !array_key_exists('service', $payload);
                })
            )
            ->willReturn($this->makeResponse());

        $response = $this->service->verifyNaturalPerson(
            new NaturalPersonInquiryDTO(
                identificationNo: '0987654321',
                name:             'علی',
                family:           'صارمی',
                birthDate:        '13541101',
                fatherName:       'یوسف',
                certificateNo:    '10984',
                gender:           1,
            ),
        );

        $this->assertSame(200, $response->get('response'));
    }

    public function test_verify_natural_foreign_uses_passport_type_and_nationality(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/estelaam',
                $this->callback(function (array $payload) {
                    return $payload['identificationType'] === 1
                        && $payload['nationality'] === 'USA'
                        && !array_key_exists('certificateNo', $payload);
                })
            )
            ->willReturn($this->makeResponse());

        $this->service->verifyNaturalPerson(
            new NaturalPersonInquiryDTO(
                identificationNo:   'P45887457',
                name:               'JOHN',
                family:             'HOPKINS',
                birthDate:          '19830813',
                identificationType: InquiryIdentificationType::Passport,
                fatherName:         'GEORGE',
                nationality:        'USA',
            ),
        );
    }

    public function test_verify_natural_includes_address_and_service_blocks(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/estelaam',
                $this->callback(function (array $payload) {
                    return $payload['address']['postalCode'] === '1576653133'
                        && $payload['address']['provinceCode'] === '021'
                        && $payload['service'] === ['serviceType' => 39];
                })
            )
            ->willReturn($this->makeResponse());

        $this->service->verifyNaturalPerson(
            person: new NaturalPersonInquiryDTO(
                identificationNo: '0987654321',
                name:             'علی',
                family:           'صارمی',
                birthDate:        '13541101',
                fatherName:       'یوسف',
                certificateNo:    '10984',
            ),
            address: new AddressDTO(
                provinceCode: '021',
                address:      'خیابان مطهری',
                houseNumber:  '272',
                postalCode:   '1576653133',
                townshipName: 'تهران',
                street2:      'شهید تیموری',
            ),
            serviceType: 39,
        );
    }

    public function test_verify_legal_iranian_sends_company_fields(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/estelaam',
                $this->callback(function (array $payload) {
                    return $payload['identificationType'] === 5
                        && $payload['companyName'] === 'آریا مهر تجارت نوین'
                        && $payload['companyType'] === 1
                        && $payload['registrationNo'] === '475771'
                        && !array_key_exists('agentIdentificationNo', $payload);
                })
            )
            ->willReturn($this->makeResponse());

        $this->service->verifyLegalPerson(
            new LegalPersonInquiryDTO(
                identificationNo: '56235625365263',
                companyName:      'آریا مهر تجارت نوین',
                companyType:      1,
                registrationDate: '13940424',
                registrationNo:   '475771',
            ),
        );
    }

    public function test_verify_legal_foreign_uses_fida_type(): void
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->with(
                'rest/shahkar/estelaam',
                $this->callback(fn (array $payload) => $payload['identificationType'] === 6)
            )
            ->willReturn($this->makeResponse());

        $this->service->verifyLegalPerson(
            new LegalPersonInquiryDTO(
                identificationNo:   '56235625365263',
                companyName:        'Benz',
                companyType:        1,
                registrationDate:   '13940424',
                registrationNo:     '475771',
                identificationType: InquiryIdentificationType::FidaId,
            ),
        );
    }

    public function test_not_found_result_is_surfaced_in_body(): void
    {
        $this->httpClient
            ->method('post')
            ->willReturn($this->makeResponse(610, 'CustomerNotFoundException'));

        $response = $this->service->verifyNaturalPerson(
            new NaturalPersonInquiryDTO('1', 'a', 'b', '13541101'),
        );

        $this->assertSame(610, $response->get('response'));
        $this->assertSame('CustomerNotFoundException', $response->get('result'));
    }
}
