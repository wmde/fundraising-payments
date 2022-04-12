<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DummyPaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\Inspectors\PayPalPaymentInspector;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment
 */
class PayPalPaymentTest extends TestCase {

	private const PAYER_ID = '42';
	private const FOLLOWUP_PAYMENT_ID = 99;

	public function testNewPayPalPaymentsAreUnbooked(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );
		$this->assertTrue( $payment->canBeBooked( PayPalPaymentBookingData::newValidBookingData() ) );
	}

	public function testCompletePaymentWithEmptyTransactionDataFails(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Transaction data must have payer ID' );

		$payment->bookPayment( [], new DummyPaymentIdRepository() );
	}

	public function testBookPaymentWithValidTransactionMarksItCompleted(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$this->assertTrue( $payment->isBooked() );
	}

	public function testBookPaymentSetsValuationDate(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( [ 'payer_id' => self::PAYER_ID, 'payment_date' => '2022-01-01 01:01:01' ], new DummyPaymentIdRepository() );

		$this->assertEquals( new \DateTimeImmutable( '2022-01-01 01:01:01' ), $payment->getValuationDate() );
	}

	public function testInitialPaymentCanBeBookedAsFollowupPayment(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::Monthly );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$this->assertTrue( $payment->canBeBooked( PayPalPaymentBookingData::newValidFollowupBookingData() ) );
	}

	public function testBookPaymentAnonymisesPersonalData(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

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

	public function testBookingABookedParentPaymentCreatesABookedChildPayment(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::Monthly );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$childPayment = $payment->bookPayment(
			PayPalPaymentBookingData::newValidFollowupBookingData(),
			$this->makeIdGeneratorForFollowupPayments()
		);

		$this->assertNotSame( $childPayment, $payment, 'Parent and followup payment should be different instances' );
		$this->assertSame( self::FOLLOWUP_PAYMENT_ID, $childPayment->getId() );
		$this->assertFalse( $childPayment->canBeBooked( PayPalPaymentBookingData::newValidBookingData() ) );
		$inspectedParentPayment = new PayPalPaymentInspector( $payment );
		$inspectedChildPayment = new PayPalPaymentInspector( $childPayment );
		$this->assertSame( self::FOLLOWUP_PAYMENT_ID, $childPayment->getId() );
		$this->assertEquals( $inspectedParentPayment->getAmount(), $inspectedChildPayment->getAmount() );
		$this->assertEquals( $inspectedParentPayment->getInterval(), $inspectedChildPayment->getInterval() );
		$this->assertSame( $payment, $inspectedChildPayment->getParentPayment() );
	}

	public function testCreateFollowupDisallowsFollowUpsFromChildPayments(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::Monthly );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );
		$childPayment = $payment->bookPayment(
			PayPalPaymentBookingData::newValidFollowupBookingData(),
			$this->makeIdGeneratorForFollowupPayments()
		);

		$this->assertFalse( $childPayment->canBeBooked( PayPalPaymentBookingData::newValidFollowupBookingData() ) );
		$this->expectException( \DomainException::class );

		$childPayment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );
	}

	public function testCreateFollowupDisallowsFollowUpsFromNonRecurringPayments(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$this->assertFalse( $payment->canBeBooked( PayPalPaymentBookingData::newValidFollowupBookingData() ) );
		$this->expectException( \DomainException::class );

		$payment->bookPayment(
			PayPalPaymentBookingData::newValidFollowupBookingData(),
			$this->makeIdGeneratorForFollowupPayments()
		);
	}

	public function testGetLegacyDataIsEmptyForUnbookedPayments(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$legacyData = $payment->getLegacyData();

		$this->assertSame( [], $legacyData->paymentSpecificValues );
		$this->assertSame( 'PPL', $legacyData->paymentName );
	}

	public function testGetLegacyDataHasDataForBookedPayments(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$legacyData = $payment->getLegacyData();

		$this->assertNotEmpty( $legacyData->paymentSpecificValues );
		// spot-check some values to see if we have the right field names
		$this->assertSame( '42', $legacyData->paymentSpecificValues['paypal_payer_id'] );
		$this->assertSame( '8RHHUM3W3PRH7QY6B59', $legacyData->paymentSpecificValues['ext_subscr_id'] );
		$this->assertSame( '2022-01-01 01:01:01', $legacyData->paymentSpecificValues['ext_payment_timestamp'] );
	}

	private function makeIdGeneratorForFollowupPayments(): PaymentIDRepository {
		$idGeneratorStub = $this->createStub( PaymentIDRepository::class );
		$idGeneratorStub->method( 'getNewID' )->willReturn( self::FOLLOWUP_PAYMENT_ID );
		return $idGeneratorStub;
	}

}
