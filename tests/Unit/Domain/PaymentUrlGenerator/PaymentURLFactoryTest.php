<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\PaymentUrlGenerator;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\AdditionalPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCard;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\CreditCardConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\NullGenerator;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentURLFactory;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPal;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PayPalConfig;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\SofortConfig;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentURLFactory;
 */
class PaymentURLFactoryTest extends TestCase {

	/**
	 * @param string $paymentType
	 * @param class-string $expectedGenerator
	 * @dataProvider paymentMethodProvider
	 */
	public function testPaymentURLFactoryReturnsNewSofortURLGenerator( string $paymentType, string $expectedGenerator ): void {
		$creditCardConfig = $this->createStub( CreditCardConfig::class );
		$payPalConfig = $this->createStub( PayPalConfig::class );
		$sofortConfig = $this->createStub( SofortConfig::class );
		$sofortClient = $this->createStub( SofortClient::class );
		$urlFactory = new PaymentURLFactory(
			$creditCardConfig,
			$payPalConfig,
			$sofortConfig,
			$sofortClient
		);

		$additionalPaymentDataStub = $this->createStub( AdditionalPaymentData::class );
		$actualResult = $urlFactory->createURLGenerator( $paymentType, $additionalPaymentDataStub );

		self::assertInstanceOf( $expectedGenerator, $actualResult );
	}

	/**
	 * @return iterable<array{string,class-string}>
	 */
	public function paymentMethodProvider(): iterable {
		yield [ 'SUB', Sofort::class ];
		yield [ 'MCP', CreditCard::class ];
		yield [ 'PPL', PayPal::class ];
		yield [ 'FAULTC0iN', NullGenerator::class ];
	}
}
