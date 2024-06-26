<?php
declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DummyPaymentIdRepository;

#[CoversClass( PayPalPayment::class )]
class PayPalPaymentTest extends TestCase {

	private const PAYER_ID = '42DFPNJDF8RED';
	private const FOLLOWUP_PAYMENT_ID = 99;

	public function testNewPayPalPaymentsAreUnbookedAndIncomplete(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );
		$this->assertTrue( $payment->canBeBooked( PayPalPaymentBookingData::newValidBookingData() ) );
		$this->assertFalse( $payment->isCompleted() );
		$this->assertNull( $payment->getTransactionId() );
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
		$this->assertTrue( $payment->isCompleted() );
	}

	public function testBookPaymentSetsValuationDate(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( $this->newMinimalBookingData(), new DummyPaymentIdRepository() );

		$this->assertEquals( new \DateTimeImmutable( '2022-01-01 01:01:01' ), $payment->getValuationDate() );
	}

	public function testBookPaymentSetsTransactionId(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );

		$payment->bookPayment( $this->newMinimalBookingData(), new DummyPaymentIdRepository() );

		$this->assertEquals( PayPalPaymentBookingData::TRANSACTION_ID, $payment->getTransactionId() );
	}

	public function testInitialPaymentCanBeBookedAsFollowupPayment(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::Monthly );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$this->assertTrue( $payment->canBeBooked( PayPalPaymentBookingData::newValidFollowupBookingData() ) );
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
		$this->assertSame( $payment, $childPayment->getParentPayment() );
		$this->assertSame( self::FOLLOWUP_PAYMENT_ID, $childPayment->getId() );
		$this->assertEquals( $payment->getAmount(), $childPayment->getAmount() );
		$this->assertEquals( $payment->getInterval(), $childPayment->getInterval() );
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

	public function testGetLegacyDataForUnbookedPayments(): void {
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
		$this->assertSame( '42DFPNJDF8RED', $legacyData->paymentSpecificValues['paypal_payer_id'] );
		$this->assertSame( '8RHHUM3W3PRH7QY6B59', $legacyData->paymentSpecificValues['ext_subscr_id'] );
		$this->assertSame( PayPalPaymentBookingData::PAYMENT_DATE, $legacyData->paymentSpecificValues['ext_payment_timestamp'] );
		// Check booked status
		$this->assertArrayNotHasKey( 'parent_payment_id', $legacyData->paymentSpecificValues, "initial payments should not have parent payment id" );
	}

	public function testGetLegacyDataHasParentPaymentIdForFollowupPayments(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::Monthly );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );
		$childPayment = $payment->bookPayment(
			PayPalPaymentBookingData::newValidFollowupBookingData(),
			$this->makeIdGeneratorForFollowupPayments()
		);

		$legacyData = $childPayment->getLegacyData();

		$this->assertNotEmpty( $legacyData->paymentSpecificValues );
		$this->assertSame( 1, $legacyData->paymentSpecificValues['parent_payment_id'] );
	}

	public function testGetDisplayDataReturnsAllFieldsToDisplayForBookedPayment(): void {
		$payment = new PayPalPayment( 1, Euro::newFromCents( 1000 ), PaymentInterval::OneTime );
		$payment->bookPayment( PayPalPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$expectedOutput = [
			'amount' => 1000,
			'interval' => 0,
			'paymentType' => 'PPL',
			'paypal_payer_id' => '42DFPNJDF8RED',
			'paypal_subscr_id' => '8RHHUM3W3PRH7QY6B59',
			'paypal_payer_status' => 'verified',
			'paypal_mc_gross' => '2.70',
			'paypal_mc_currency' => 'EUR',
			'paypal_mc_fee' => '2.70',
			'paypal_settle_amount' => '2.70',
			'ext_payment_id' => 'T4242',
			'ext_subscr_id' => '8RHHUM3W3PRH7QY6B59',
			'ext_payment_type' => 'instant',
			'ext_payment_status' => 'processed/express_checkout',
			'ext_payment_account' => '42DFPNJDF8RED',
			'ext_payment_timestamp' => PayPalPaymentBookingData::PAYMENT_DATE
		];

		$actualDisplayData = $payment->getDisplayValues();

		$this->assertNotNull( $payment->getValuationDate() );
		$this->assertFalse( $payment->canBeBooked( [] ) );
		$this->assertEquals( $expectedOutput, $actualDisplayData );
	}

	private function makeIdGeneratorForFollowupPayments(): PaymentIdRepository {
		$idGeneratorStub = $this->createStub( PaymentIdRepository::class );
		$idGeneratorStub->method( 'getNewId' )->willReturn( self::FOLLOWUP_PAYMENT_ID );
		return $idGeneratorStub;
	}

	/**
	 * @return array<string,string>
	 */
	private function newMinimalBookingData(): array {
		return [
			'payer_id' => self::PAYER_ID,
			'payment_date' => '01:01:01 Jan 01, 2022 UTC',
			'txn_id' => PayPalPaymentBookingData::TRANSACTION_ID
		];
	}

}
