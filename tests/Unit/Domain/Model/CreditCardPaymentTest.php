<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardTransactionData;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment
 */
class CreditCardPaymentTest extends TestCase {

	private const TRANSACTION_ID = '42';

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
}
