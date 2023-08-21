<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Tests\Data\DomainSpecificContextForTesting;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FailingDomainSpecificValidator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SucceedingDomainSpecificValidator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DomainSpecificPaymentCreationRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DomainSpecificPaymentCreationRequest
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest
 */
class DomainSpecificPaymentCreationRequestTest extends TestCase {
	public function testRequestCanBeStringified(): void {
		$request = new DomainSpecificPaymentCreationRequest(
			9876,
			1,
			'BEZ',
			new SucceedingDomainSpecificValidator(),
			DomainSpecificContextForTesting::create(),
			new FakeUrlAuthenticator(),
			'DE88100900001234567892',
			'BEVODEBB',
			'D'
		);

		$this->assertSame(
			'{"amountInEuroCents":9876,"interval":1,"paymentType":"BEZ","iban":"DE88100900001234567892","bic":"BEVODEBB","transferCodePrefix":"D","domainSpecificPaymentValidator":"WMDE\\\\Fundraising\\\\PaymentContext\\\\Tests\\\\Fixtures\\\\SucceedingDomainSpecificValidator","domainSpecificContext":{"itemId":1,"startTimeForRecurringPayment":null,"invoiceId":"D-1","firstName":"Hubert J.","lastName":"Farnsworth"}}',
			(string)$request
		);
	}

	public function testGivenInvalidInputStringifiedOutputIsErrorMessage(): void {
		$request = new DomainSpecificPaymentCreationRequest(
			9876,
			1,
			'BEZ',
			new SucceedingDomainSpecificValidator(),
			DomainSpecificContextForTesting::create(),
			new FakeUrlAuthenticator(),
			"\xB1\x31",
		);

		$requestAsString = (string)$request;

		$this->assertStringContainsString( 'JSON encode error', $requestAsString );
		$this->assertStringContainsString(
			'::__toString: Malformed UTF-8 characters, possibly incorrectly encoded',
			$requestAsString
		);
	}

	public function testJSONRepresentationContainsValidatorClassName(): void {
		$request = new DomainSpecificPaymentCreationRequest(
			9876,
			1,
			'BEZ',
			new FailingDomainSpecificValidator(),
			DomainSpecificContextForTesting::create(),
			new FakeUrlAuthenticator(),
			'DE88100900001234567892',
			'BEVODEBB',
			'D'
		);

		$json = $request->jsonSerialize();

		$this->assertIsObject( $json );
		$this->assertInstanceOf( 'stdClass', $json );
		$this->assertSame(
			'WMDE\\Fundraising\\PaymentContext\\Tests\\Fixtures\\FailingDomainSpecificValidator',
			$json->domainSpecificPaymentValidator
		);
	}

	public function testNewFromBaseRequestPassesConstructorParameters(): void {
		$request = DomainSpecificPaymentCreationRequest::newFromBaseRequest(
			new PaymentCreationRequest(
			9876,
			1,
			'BEZ',
			'DE88100900001234567892',
			'BEVODEBB',
			'D'
			),
			new SucceedingDomainSpecificValidator(),
			DomainSpecificContextForTesting::create(),
			new FakeUrlAuthenticator(),
		);

		$this->assertSame( 9876, $request->amountInEuroCents );
		$this->assertSame( 1, $request->interval );
		$this->assertSame( 'BEZ', $request->paymentType );
		$this->assertSame( 'DE88100900001234567892', $request->iban );
		$this->assertSame( 'BEVODEBB', $request->bic );
		$this->assertSame( 'D', $request->transferCodePrefix );
	}

}
