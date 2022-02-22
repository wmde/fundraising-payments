<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use DateTime;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentTransactionData;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortTransactionData;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment
 */
class SofortPaymentTest {

	public function testInitialProperties(): void {
		$sofortPayment = new SofortPayment( 'lorem' );
		$this->assertSame( 'SUB', $sofortPayment->getId() );
		$this->assertSame( 'lorem', $sofortPayment->getBankTransferCode() );
		$this->assertNull( $sofortPayment->getConfirmedAt() );
	}

	public function testConfirmedAcceptsDateTime(): void {
		$sofortPayment = new SofortPayment( 'lorem' );
		$sofortPayment->setConfirmedAt( new DateTime( '2001-12-24T17:30:00Z' ) );
		$this->assertEquals( new DateTime( '2001-12-24T17:30:00Z' ), $sofortPayment->getConfirmedAt() );
	}

	public function testIsConfirmedPayment_newPaymentIsNotConfirmed(): void {
		$sofortPayment = new SofortPayment( 'ipsum' );
		$this->assertFalse( $sofortPayment->isConfirmedPayment() );
	}

	public function testIsConfirmedPayment_settingPaymentDateConfirmsPayment(): void {
		$sofortPayment = new SofortPayment( 'ipsum' );
		$sofortPayment->setConfirmedAt( new DateTime( 'now' ) );
		$this->assertTrue( $sofortPayment->isConfirmedPayment() );
	}

	public function testPaymentWithoutDate_isUncompleted(): void {
		$sofortPayment = new SofortPayment( 'ipsum' );
		$this->assertFalse( $sofortPayment->paymentCompleted() );
	}

	public function testPaymentWithDate_isCompleted(): void {
		$sofortPayment = new SofortPayment( 'ipsum' );
		$sofortPayment->setConfirmedAt( new DateTime( 'now' ) );
		$this->assertTrue( $sofortPayment->paymentCompleted() );
	}

	public function testCompletePaymentWithInvalidTransactionObjectFails(): void {
		$payment = new SofortPayment( 'ipsum' );
		$wrongPaymentTransaction = new class() implements PaymentTransactionData {
		};

		$this->expectException( \InvalidArgumentException::class );

		$payment->bookPayment( $wrongPaymentTransaction );
	}

	public function testGivenCompletedPayment_completePaymentFails(): void {
		$payment = new SofortPayment( 'ipsum' );
		$firstCompletion = new SofortTransactionData( new \DateTimeImmutable() );
		$secondCompletion = new SofortTransactionData( new \DateTimeImmutable( '2021-12-24 0:00:00' ) );
		$payment->bookPayment( $firstCompletion );

		$this->expectException( \DomainException::class );

		$payment->bookPayment( $secondCompletion );
	}

	public function testCompletePaymentWithValidTransactionDataSucceeds(): void {
		$payment = new SofortPayment( 'ipsum' );
		$valuationDate = new \DateTimeImmutable();
		$transactionData = new SofortTransactionData( $valuationDate );

		$payment->bookPayment( $transactionData );

		$this->assertTrue( $payment->paymentCompleted() );
		$this->assertEquals( $valuationDate, $payment->getValuationDate() );
	}

}
