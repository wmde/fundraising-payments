<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalData;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment
 */
class PayPalPaymentTest extends TestCase {

	private const PAYER_ID = '42';

	public function testGivenPaypalDataWithNoPayerID_isUncompleted(): void {
		$payPalPayment = new PayPalPayment( new PayPalData() );
		$this->assertFalse( $payPalPayment->paymentCompleted() );
	}

	public function testGivenPaypalDataWithPayerID_isCompleted(): void {
		$payPalPayment = new PayPalPayment( ( new PayPalData() )->setPayerId( self::PAYER_ID ) );
		$this->assertTrue( $payPalPayment->paymentCompleted() );
	}
}
