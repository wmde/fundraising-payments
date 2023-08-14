<?php

declare( strict_types = 1 );

namespace Unit\Services;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Services\PaymentURLFactory;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\CreditCardURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\NullGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalAPIURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\PayPalAPIURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\SofortClient;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PaymentURLFactory
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
		$urlFactory = $this->createTestURLFactory( PayPalAPIURLGeneratorConfig::class );
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
	 * @param class-string<LegacyPayPalURLGeneratorConfig|PayPalAPIURLGeneratorConfig> $paypalConfigClassName
	 */
	private function createTestURLFactory( string $paypalConfigClassName = LegacyPayPalURLGeneratorConfig::class ): PaymentURLFactory {
		$creditCardConfig = $this->createStub( CreditCardURLGeneratorConfig::class );
		$payPalConfig = $this->createStub( $paypalConfigClassName );
		$sofortConfig = $this->createStub( SofortURLGeneratorConfig::class );
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
