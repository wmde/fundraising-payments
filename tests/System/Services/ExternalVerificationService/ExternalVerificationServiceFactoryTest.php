<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\System\Services\ExternalVerificationService;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\ExternalVerificationServiceFactory;
use WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\PayPal\PayPalVerificationService;
use WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\SucceedingVerificationService;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\ExternalVerificationServiceFactory
 */
class ExternalVerificationServiceFactoryTest extends TestCase {

	public function testOnGivenPayPalPaymentReturnsPayPalVerifier(): void {
		$factory = new ExternalVerificationServiceFactory( new Client(), 'Any string', 'Any string' );
		$payPalPayment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$this->assertInstanceOf( PayPalVerificationService::class, $factory->create( $payPalPayment ) );
	}

	public function testOnGivenNonPayPalPaymentReturnsSucceedingVerifier(): void {
		$factory = new ExternalVerificationServiceFactory( new Client(), 'Any string', 'Any string' );
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$this->assertInstanceOf( SucceedingVerificationService::class, $factory->create( $creditCardPayment ) );
	}
}
