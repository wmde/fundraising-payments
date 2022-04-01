<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Tests\Data\CreditCardPaymentBookingData;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment
 */
class CreditCardPaymentTest extends TestCase {

	private const OTHER_TRANSACTION_ID = '3388998877';

	public function testNewCreditCardPaymentsAreNotBooked(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$this->assertTrue( $creditCardPayment->canBeBooked( [] ) );
	}

	public function testCompletePaymentWithOutTransactionIdFails(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$this->expectException( \InvalidArgumentException::class );

		$creditCardPayment->bookPayment( [] );
	}

	public function testCompletePaymentWithEmptyTransactionDataFails(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$this->expectException( \InvalidArgumentException::class );

		$creditCardPayment->bookPayment( [ 'transactionId' => '' ] );
	}

	public function testPaymentCannotBeBookedMultipleTimes(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$creditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData() );

		$this->expectException( \DomainException::class );

		$creditCardPayment->bookPayment( [
			...CreditCardPaymentBookingData::newValidBookingData(),
			'transactionId' => self::OTHER_TRANSACTION_ID
		] );
	}

	public function testBookPaymentWithValidTransactionMarksItBooked(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$creditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData() );

		$this->assertFalse( $creditCardPayment->canBeBooked( CreditCardPaymentBookingData::newValidBookingData() ) );
		// Credit cards get their valuation date from current time instead of transaction data
		$this->assertNotNull( $creditCardPayment->getValuationDate() );
		$this->assertEqualsWithDelta( time(), $creditCardPayment->getValuationDate()->getTimestamp(), 5 );
	}

	public function testGivenBookedPaymentGetLegacyDataReturnsNonEmptyArray(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$creditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData() );

		$this->assertNotEmpty( $creditCardPayment->getLegacyData() );
	}

	public function testGivenNewPaymentGetLegacyDataReturnsEmptyArray(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$this->assertSame( [],  $creditCardPayment->getLegacyData() );
	}
}
