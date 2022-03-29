<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\PayPalPaymentInspector;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment
 */
class PayPalPaymentTest extends TestCase {

	private const PAYER_ID = '42';

	public function testNewPayPalPaymentsAreUncompleted(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );
		$this->assertFalse( $payment->isCompleted() );
	}

	public function testCompletePaymentWithEmptyTransactionDataFails(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Transaction data must have payer ID' );

		$payment->bookPayment( [] );
	}

	public function testBookPaymentWithValidTransactionMarksItCompleted(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( [ 'payer_id' => self::PAYER_ID, 'payment_date' => '2022-01-01 01:01:01' ] );

		$this->assertTrue( $payment->isCompleted() );
	}

	public function testBookPaymentSetsValuationDate(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( [ 'payer_id' => self::PAYER_ID, 'payment_date' => '2022-01-01 01:01:01' ] );

		$this->assertEquals( new \DateTimeImmutable( '2022-01-01 01:01:01' ), $payment->getValuationDate() );
	}

	public function testPaymentCannotBeBookedMultipleTimes(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData() );

		$this->expectException( \DomainException::class );
		$this->expectExceptionMessage( 'Payment is already completed' );

		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData() );
	}

	public function testBookPaymentAnonymisesPersonalData(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData() );

		$bookingData = ( new PayPalPaymentInspector( $payment ) )->getBookingData();
		$this->assertArrayNotHasKey( 'first_name', $bookingData );
		$this->assertArrayNotHasKey( 'last_name', $bookingData );
		$this->assertArrayNotHasKey( 'address_name', $bookingData );
		$this->assertArrayNotHasKey( 'address_street', $bookingData );
		$this->assertArrayNotHasKey( 'address_status', $bookingData );
		$this->assertArrayNotHasKey( 'address_zip', $bookingData );
		$this->assertArrayNotHasKey( 'address_city', $bookingData );
		$this->assertArrayNotHasKey( 'address_country_code', $bookingData );
	}

	public function testCreateFollowupCopiesAmountAndInterval(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$childPayment = $payment->createFollowUpPayment( 2 );

		$inspectedParentPayment = new PayPalPaymentInspector( $payment );
		$inspectedChildPayment = new PayPalPaymentInspector( $childPayment );
		$this->assertSame( 2, $childPayment->getId() );
		$this->assertEquals( $inspectedParentPayment->getAmount(), $inspectedChildPayment->getAmount() );
		$this->assertEquals( $inspectedParentPayment->getInterval(), $inspectedChildPayment->getInterval() );
		$inspector = new PayPalPaymentInspector( $childPayment );
		$this->assertSame( $payment, $inspector->getParentPayment() );
	}

	public function testFollowupPaymentIsUnbooked(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData() );

		$childPayment = $payment->createFollowUpPayment( 2 );

		$this->assertFalse( $childPayment->isCompleted() );
	}

	public function testCreateFollowupDisallowsFollowUpsFromChildPayments(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData() );
		$childPayment = $payment->createFollowUpPayment( 2 );

		$this->expectException( \RuntimeException::class );

		$childPayment->createFollowUpPayment( 3 );
	}

}
