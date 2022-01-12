<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentTransactionData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment
 */
class PayPalPaymentTest extends TestCase {

	private const PAYER_ID = '42';
	private const OTHER_PAYER_ID = '23';

	public function testGivenPaypalDataWithNoPayerID_isUncompleted(): void {
		$payPalPayment = new PayPalPayment( new PayPalData() );
		$this->assertFalse( $payPalPayment->paymentCompleted() );
	}

	public function testGivenPaypalDataWithPayerID_isCompleted(): void {
		$payPalPayment = new PayPalPayment( ( new PayPalData() )->setPayerId( self::PAYER_ID ) );
		$this->assertTrue( $payPalPayment->paymentCompleted() );
	}

	public function testCompletePaymentWithInvalidTransactionObjectFails(): void {
		$payment = new PayPalPayment( new PayPalData() );
		$wrongPaymentTransaction = new class() implements PaymentTransactionData {
		};

		$this->expectException( \InvalidArgumentException::class );

		$payment->bookPayment( $wrongPaymentTransaction );
	}

	public function testCompletePaymentWithEmptyPayerId(): void {
		$payment = new PayPalPayment( new PayPalData() );

		$this->expectException( \InvalidArgumentException::class );

		$payment->bookPayment( new PayPalData() );
	}

	public function testGivenCompletedPayment_completePaymentFails(): void {
		$transactionData = new PayPalData();
		$transactionData->setPayerId( self::PAYER_ID );
		$payment = new PayPalPayment( $transactionData );
		$newTransactionData = new PayPalData();
		$newTransactionData->setPayerId( self::OTHER_PAYER_ID );

		$this->expectException( \DomainException::class );

		$payment->bookPayment( $newTransactionData );
	}

	public function testCompletePaymentWithValidTransactionDataSucceeds(): void {
		$payment = new PayPalPayment( new PayPalData() );
		$newTransactionData = new PayPalData();
		$newTransactionData->setPayerId( self::PAYER_ID );

		$payment->bookPayment( $newTransactionData );

		$this->assertTrue( $payment->paymentCompleted() );
		$this->assertSame( self::PAYER_ID, $payment->getPayPalData()->getPayerId() );
	}
}
