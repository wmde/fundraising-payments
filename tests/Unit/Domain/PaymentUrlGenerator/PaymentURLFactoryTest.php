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
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCardConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCardURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\LegacyPayPalConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\LegacyPayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\NullGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentURLFactory;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalAPIConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalAPIURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\SofortConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\SofortURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentURLFactory
 */
class PaymentURLFactoryTest extends TestCase {

	public function testPaymentURLFactoryCreatesSofortURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();
		$payment = SofortPayment::create(
			1,
			Euro::newFromCents( 1000 ),
			PaymentInterval::OneTime,
			new PaymentReferenceCode( 'XW', 'DARE99', 'X' )
		);

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( SofortURLGenerator::class, $actualGenerator );
	}

	public function testPaymentURLFactoryCreatesCreditCardURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();
		$payment = new CreditCardPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( CreditCardURLGenerator::class, $actualGenerator );
	}

	public function testPaymentURLFactoryCreatesLegacyPayPalURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();
		$payment = new PayPalPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( LegacyPayPalURLGenerator::class, $actualGenerator );
	}

	public function testPaymentURLFactoryCreatesPayPalAPIURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory( PayPalAPIConfig::class );
		$payment = new PayPalPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( PayPalAPIURLGenerator::class, $actualGenerator );
	}

	public function testPaymentURLFactoryCreatesNullURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();

		$payment = $this->createMock( Payment::class );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( NullGenerator::class, $actualGenerator );
	}

	/**
	 * @param class-string<LegacyPayPalConfig|PayPalAPIConfig> $paypalConfigClassName
	 */
	private function createTestURLFactory( string $paypalConfigClassName = LegacyPayPalConfig::class ): PaymentURLFactory {
		$creditCardConfig = $this->createStub( CreditCardConfig::class );
		$payPalConfig = $this->createStub( $paypalConfigClassName );
		$sofortConfig = $this->createStub( SofortConfig::class );
		$sofortClient = $this->createStub( SofortClient::class );
		$payPalApiClient = $this->createStub( PaypalAPI::class );
		return new PaymentURLFactory(
			$creditCardConfig,
			$payPalConfig,
			$payPalApiClient,
			$sofortConfig,
			$sofortClient
		);
	}
}
