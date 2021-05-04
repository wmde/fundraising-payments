<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardTransactionData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentTransactionData;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment
 */
class CreditCardPaymentTest extends TestCase {

	private const TRANSACTION_ID = '7788998877';
	private const OTHER_TRANSACTION_ID = '3388998877';

	public function testGivenNullCreditCardTransactionData_isUncompleted(): void {
		$creditCardPayment = new CreditCardPayment();
		$this->assertFalse( $creditCardPayment->paymentCompleted() );
	}

	public function testGivenCreditCardTransactionDataWithNoTransactionID_isUncompleted(): void {
		$creditCardPayment = new CreditCardPayment( new CreditCardTransactionData() );
		$this->assertFalse( $creditCardPayment->paymentCompleted() );
	}

	public function testGivenCreditCardTransactionDataWithTransactionID_isCompleted(): void {
		$creditCardPayment = new CreditCardPayment( ( new CreditCardTransactionData() )->setTransactionId( self::TRANSACTION_ID ) );
		$this->assertTrue( $creditCardPayment->paymentCompleted() );
	}

	public function testCompletePaymentWithInvalidTransactionObjectFails(): void {
		$creditCardPayment = new CreditCardPayment( new CreditCardTransactionData() );
		$wrongPaymentTransaction = new class() implements PaymentTransactionData {
		};

		$this->expectException( \InvalidArgumentException::class );

		$creditCardPayment->bookPayment( $wrongPaymentTransaction );
	}

	public function testCompletePaymentWithEmptyTransactionDataFails(): void {
		$creditCardPayment = new CreditCardPayment( new CreditCardTransactionData() );

		$this->expectException( \InvalidArgumentException::class );

		$creditCardPayment->bookPayment( new CreditCardTransactionData() );
	}

	public function testGivenCompletedPayment_completePaymentFails(): void {
		$transactionData = new CreditCardTransactionData();
		$transactionData->setTransactionId( self::TRANSACTION_ID );
		$creditCardPayment = new CreditCardPayment( $transactionData );
		$newTransactionData = new CreditCardTransactionData();
		$newTransactionData->setTransactionId( self::OTHER_TRANSACTION_ID );

		$this->expectException( \DomainException::class );

		$creditCardPayment->bookPayment( $newTransactionData );
	}

	public function testCompletePaymentWithValidTransactionDataSucceeds(): void {
		$creditCardPayment = new CreditCardPayment( new CreditCardTransactionData() );
		$transactionData = new CreditCardTransactionData();
		$transactionData->setTransactionId( self::TRANSACTION_ID );

		$creditCardPayment->bookPayment( $transactionData );

		$this->assertTrue( $creditCardPayment->paymentCompleted() );
		$this->assertSame( self::TRANSACTION_ID, $creditCardPayment->getCreditCardData()->getTransactionId() );
	}
}
