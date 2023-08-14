<?php
declare( strict_types=1 );

namespace Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalOrder;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalOrder
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPaymentIdentifier
 */
class PayPalOrderTest extends TestCase {
	private const TRANSACTION_ID = '2GG279541U471931P';

	public function testConstructorAllowsOnlyForOneTimePayment(): void {
		$this->expectException( \DomainException::class );

		new PayPalOrder( new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::Monthly ), self::TRANSACTION_ID );
	}

	/**
	 * @dataProvider provideEmptyTransactionIDs
	 */
	public function testConstructorEnforcesNonEmptyTransactionId( string $transactionId ): void {
		$this->expectException( \DomainException::class );

		new PayPalOrder( $this->givenOneTimePayment(), $transactionId );
	}

	/**
	 * @return iterable<array{string}>
	 */
	public static function provideEmptyTransactionIDs(): iterable {
		yield 'empty string' => [ '' ];
		yield 'string consisting of spaces' => [ '   ' ];
		yield 'string consisting of whitespace characters' => [ "\t\r\n " ];
	}

	public function testConstructorSetsProperties(): void {
		$payment = $this->givenOneTimePayment();
		$order = new PayPalOrder( $payment, self::TRANSACTION_ID );

		$this->assertSame( $payment, $order->getPayment() );
		$this->assertSame( self::TRANSACTION_ID, $order->getTransactionId() );
	}

	private function givenOneTimePayment(): PayPalPayment {
	return new PayPalPayment( 1, Euro::newFromCents( 5000 ), PaymentInterval::OneTime );
	}
}
