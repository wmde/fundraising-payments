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
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\IncompletePayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\LegacyPayPalURLGeneratorConfig;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\NullGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\SofortClient;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\SofortURLGeneratorConfig;

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

	/**
	 * This test check the creation of the legacy URL generator,
	 * remove when the application has switched completely to the PayPal API,
	 * and we don't need the feature flag any more
	 * (see https://phabricator.wikimedia.org/T329159 )
	 *
	 * @deprecated This test runs with the legacy feature flag
	 */
	public function testPaymentURLFactoryCreatesLegacyPayPalURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory( true );
		$payment = new PayPalPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( LegacyPayPalURLGenerator::class, $actualGenerator );
	}

	public function testPaymentURLFactoryCreatesIncompletePayPalURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();
		$payment = new PayPalPayment( 1, Euro::newFromInt( 99 ), PaymentInterval::Quarterly );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		// The IncompletePayPalURLGenerator will be replaced inside the use case, we just need a default for PayPal
		self::assertInstanceOf( IncompletePayPalURLGenerator::class, $actualGenerator );
	}

	public function testPaymentURLFactoryCreatesNullURLGenerator(): void {
		$urlFactory = $this->createTestURLFactory();

		$payment = $this->createMock( Payment::class );

		$actualGenerator = $urlFactory->createURLGenerator( $payment );

		self::assertInstanceOf( NullGenerator::class, $actualGenerator );
	}

	private function createTestURLFactory( bool $useLegacyPayPalUrlGenerator = false ): PaymentURLFactory {
		$creditCardConfig = $this->createStub( CreditCardURLGeneratorConfig::class );
		$payPalConfig = $this->createStub( LegacyPayPalURLGeneratorConfig::class );
		$sofortConfig = $this->createStub( SofortURLGeneratorConfig::class );
		$sofortClient = $this->createStub( SofortClient::class );
		return new PaymentURLFactory(
			$creditCardConfig,
			$payPalConfig,
			$sofortConfig,
			$sofortClient,
			$useLegacyPayPalUrlGenerator
		);
	}
}
