<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use DateTime;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\BasePaymentMethod;
use WMDE\Fundraising\PaymentContext\Domain\Model\InvalidPaymentTypeException;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\PaymentMethodStub;

class SofortPaymentTest extends TestCase {

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

	public function testDispatchCallsCallbackForSofortPaymentFunction(): void {
		$sofortPayment = new SofortPayment( 'ipsum' );
		$sofortPayment->dispatch( function ( SofortPayment $payment ) use ( $sofortPayment ) {
			$this->assertSame( $sofortPayment, $payment, 'Payment should be callback parameter' );
		} );
	}

	public function testDispatchReturnsCallbackResult(): void {
		$sofortPayment = new SofortPayment( 'ipsum' );
		$this->assertSame(
			'success!',
			$sofortPayment->dispatch( function ( SofortPayment $payment ) {
				return 'success!';
			} )
		);
	}

	public function testDispatchThrowsForNonSofortPaymentFunction(): void {
		$sofortPayment = new SofortPayment( 'ipsum' );
		$this->expectException( \TypeError::class );
		$sofortPayment->dispatch( function ( PaymentMethodStub $payment ) {
			$this->fail( 'Callback should not be called' );
		} );
	}
}
