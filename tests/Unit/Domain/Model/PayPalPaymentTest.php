<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment
 */
class PayPalPaymentTest extends TestCase {

	public function testGivenPaypalDataWithNoPayerID_isUncompleted(): void {
		$payPalPayment = new PayPalPayment( new PayPalData() );
		$this->assertFalse( $payPalPayment->paymentCompleted() );
	}

	public function testGivenPaypalDataWithPayerID_isCompleted(): void {
		$payPalPayment = new PayPalPayment( ( new PayPalData() )->setPayerId( 42 ) );
		$this->assertTrue( $payPalPayment->paymentCompleted() );
	}
}
