<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Tests\Data\DomainSpecificContextForTesting;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FailingDomainSpecificValidator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FakeUrlAuthenticator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SucceedingDomainSpecificValidator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentParameters;

#[CoversClass( PaymentCreationRequest::class )]
#[CoversClass( PaymentParameters::class )]
class PaymentCreationRequestTest extends TestCase {
	public function testRequestCanBeStringified(): void {
		$request = new PaymentCreationRequest(
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
			'{"amountInEuroCents":9876,"interval":1,"paymentType":"BEZ","domainSpecificPaymentValidator":"WMDE\\\\Fundraising\\\\PaymentContext\\\\Tests\\\\Fixtures\\\\SucceedingDomainSpecificValidator","domainSpecificContext":{"itemId":1,"startTimeForRecurringPayment":null,"invoiceId":"D-1","firstName":"Hubert J.","lastName":"Farnsworth"},"iban":"DE88100900001234567892","bic":"BEVODEBB","transferCodePrefix":"D"}',
			(string)$request
		);
	}

	public function testGivenInvalidInputStringifiedOutputIsErrorMessage(): void {
		$request = new PaymentCreationRequest(
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
		$request = new PaymentCreationRequest(
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

	public function testPaymentParametersRoundTrip(): void {
		$parameters = new PaymentParameters(
			9876,
			1,
			'BEZ',
			'DE88100900001234567892',
			'BEVODEBB',
			'D'
		);
		$request = PaymentCreationRequest::newFromParameters(
			$parameters,
			new SucceedingDomainSpecificValidator(),
			DomainSpecificContextForTesting::create(),
			new FakeUrlAuthenticator(),
		);

		$this->assertEquals( $parameters, $request->getParameters() );
	}

}
