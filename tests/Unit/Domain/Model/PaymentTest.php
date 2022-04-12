<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\Payment
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentData
 */
class PaymentTest extends TestCase {
	private const PAYMENT_ID = 49;

	public function testGetId(): void {
		$payment = $this->createPayment();

		$this->assertSame( self::PAYMENT_ID, $payment->getId() );
	}

	public function testGetLegacyDataCollectsPaymentInformation(): void {
		$payment = $this->createPayment();
		$expectedLegacyData = new LegacyPaymentData(
			1199,
			0,
			'TST',
			[ 'value' => 'infinite' ]
		);

		$this->assertEquals( $expectedLegacyData, $payment->getLegacyData() );
	}

	private function createPayment(): Payment {
		return new class( self::PAYMENT_ID ) extends Payment {
			public function __construct( int $id ) {
				// Our test payment hard-codes the values for amount and interval for simplicity
				parent::__construct(
					$id,
					Euro::newFromCents( 1199 ),
					PaymentInterval::OneTime,
					'TST'
				);
			}

			protected function getPaymentName(): string {
				return 'TST';
			}

			protected function getPaymentSpecificLegacyData(): array {
				return [ 'value' => 'infinite' ];
			}
		};
	}
}
