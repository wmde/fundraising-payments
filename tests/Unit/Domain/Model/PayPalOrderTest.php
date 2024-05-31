<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use DomainException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalOrder;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPaymentIdentifier;

#[CoversClass( PayPalOrder::class )]
#[CoversClass( PayPalPaymentIdentifier::class )]
class PayPalOrderTest extends TestCase {

	private const ORDER_ID = '5O190127TN364715T';
	private const TRANSACTION_ID = '2GG279541U471931P';
	private const ANOTHER_TRANSACTION_ID = '3HH380652V580042Q';

	public function testConstructorAllowsOnlyForOneTimePayment(): void {
		$this->expectException( DomainException::class );

		new PayPalOrder( new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::Monthly ), self::ORDER_ID );
	}

	#[DataProvider( 'provideEmptyIDs' )]
	public function testConstructorEnforcesNonEmptyOrderId( string $orderId ): void {
		$this->expectException( DomainException::class );

		new PayPalOrder( $this->givenOneTimePayment(), $orderId );
	}

	/**
	 * @return iterable<array{string}>
	 */
	public static function provideEmptyIDs(): iterable {
		yield 'empty string' => [ '' ];
		yield 'string consisting of spaces' => [ '   ' ];
		yield 'string consisting of whitespace characters' => [ "\t\r\n " ];
	}

	public function testConstructorSetsProperties(): void {
		$payment = $this->givenOneTimePayment();
		$order = new PayPalOrder( $payment, self::ORDER_ID, self::TRANSACTION_ID );

		$this->assertSame( $payment, $order->getPayment() );
		$this->assertSame( self::ORDER_ID, $order->getOrderId() );
		$this->assertSame( self::TRANSACTION_ID, $order->getTransactionId() );
	}

	#[DataProvider( 'provideEmptyIDs' )]
	public function testSettingEmptyTransactionIdThrowException( string $transactionId ): void {
		$order = new PayPalOrder( $this->givenOneTimePayment(), self::ORDER_ID );

		$this->expectException( DomainException::class );
		$this->expectExceptionMessage( 'Transaction ID must not be empty when setting it explicitly' );
		$order->setTransactionId( $transactionId );
	}

	public function testWhenTransactionIdHasBeenSetItMustNotChange(): void {
		$order = new PayPalOrder( $this->givenOneTimePayment(), self::ORDER_ID );
		$order->setTransactionId( self::TRANSACTION_ID );

		$this->expectException( DomainException::class );
		$this->expectExceptionMessage( 'Transaction ID must not be changed' );
		$order->setTransactionId( self::ANOTHER_TRANSACTION_ID );
	}

	private function givenOneTimePayment(): PayPalPayment {
		return new PayPalPayment( 1, Euro::newFromCents( 5000 ), PaymentInterval::OneTime );
	}
}
