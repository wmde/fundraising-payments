<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCard;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCardConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\NullGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentURLFactory;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPal;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\SofortConfig;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentURLFactory
 */
class PaymentURLFactoryTest extends TestCase {

	public function testPaymentURLFactoryReturnsNewSofortURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();
		$payment = SofortPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'XW', 'DARE99', 'X' )
		);

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( Sofort::class, $actualGenerator );
	}

	public function testPaymentURLFactoryReturnsNewCreditCardURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();
		$payment = new CreditCardPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( CreditCard::class, $actualGenerator );
	}

	public function testPaymentURLFactoryReturnsNewPayPalURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();
		$payment = new PayPalPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( PayPal::class, $actualGenerator );
	}

	public function testPaymentURLFactoryReturnsNewNullURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();

		$payment = $this->createMock( Payment::class );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( NullGenerator::class, $actualGenerator );
	}

	private function createTestURLFactory(): PaymentURLFactory {
		$creditCardConfig = $this->createStub( CreditCardConfig::class );
		$payPalConfig = $this->createStub( PayPalConfig::class );
		$sofortConfig = $this->createStub( SofortConfig::class );
		$sofortClient = $this->createStub( SofortClient::class );
		return new PaymentURLFactory(
			$creditCardConfig,
			$payPalConfig,
			$sofortConfig,
			$sofortClient
		);
	}
}
