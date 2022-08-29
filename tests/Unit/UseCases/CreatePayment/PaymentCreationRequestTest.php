<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\UseCases\CreatePayment;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\FailingDomainSpecificValidator;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\SucceedingDomainSpecificValidator;
use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest;

/**
 * @covers \WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\PaymentCreationRequest
 */
class PaymentCreationRequestTest extends TestCase {
	public function testRequestCanBeStringified(): void {
		$request = new PaymentCreationRequest( 9876, 1, 'BEZ', 'DE88100900001234567892', 'BEVODEBB', 'D' );
		$request->setDomainSpecificPaymentValidator( new SucceedingDomainSpecificValidator() );

		$this->assertSame(
			'{"domainSpecificPaymentValidator":"WMDE\\\\Fundraising\\\\PaymentContext\\\\Tests\\\\Fixtures\\\\SucceedingDomainSpecificValidator","amountInEuroCents":9876,"interval":1,"paymentType":"BEZ","iban":"DE88100900001234567892","bic":"BEVODEBB","transferCodePrefix":"D"}',
			(string)$request
		);
	}

	public function testGivenInvalidInputStringifiedOutputIsErrorMessage(): void {
		$request = new PaymentCreationRequest( 9876, 1, 'BEZ', "\xB1\x31", );
		$request->setDomainSpecificPaymentValidator( new SucceedingDomainSpecificValidator() );

		$this->assertSame(
			'JSON encode error in WMDE\\Fundraising\\PaymentContext\\UseCases\\CreatePayment\\PaymentCreationRequest::__toString: Malformed UTF-8 characters, possibly incorrectly encoded',
			(string)$request
		);
	}

	public function testJSONRepresentationContainsValidatorClassName(): void {
		$request = new PaymentCreationRequest( 9876, 1, 'BEZ', 'DE88100900001234567892', 'BEVODEBB', 'D' );
		$request->setDomainSpecificPaymentValidator( new FailingDomainSpecificValidator() );

		$json = $request->jsonSerialize();

		$this->assertIsObject( $json );
		$this->assertInstanceOf( 'stdClass', $json );
		$this->assertSame(
			'WMDE\\Fundraising\\PaymentContext\\Tests\\Fixtures\\FailingDomainSpecificValidator',
			$json->domainSpecificPaymentValidator
		);
	}
}
