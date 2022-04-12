<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Tests\Data\CreditCardPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DummyPaymentIdRepository;

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

		$creditCardPayment->bookPayment( [], new DummyPaymentIdRepository() );
	}

	public function testCompletePaymentWithEmptyTransactionDataFails(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$this->expectException( \InvalidArgumentException::class );

		$creditCardPayment->bookPayment( [ 'transactionId' => '' ], new DummyPaymentIdRepository() );
	}

	public function testPaymentCannotBeBookedMultipleTimes(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$creditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$this->expectException( \DomainException::class );

		$creditCardPayment->bookPayment(
			[
				...CreditCardPaymentBookingData::newValidBookingData(),
				'transactionId' => self::OTHER_TRANSACTION_ID
			],
			new DummyPaymentIdRepository()
		);
	}

	public function testBookPaymentWithValidTransactionMarksItBooked(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$creditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$this->assertFalse( $creditCardPayment->canBeBooked( CreditCardPaymentBookingData::newValidBookingData() ) );
		// Credit cards get their valuation date from current time instead of transaction data
		$this->assertNotNull( $creditCardPayment->getValuationDate() );
		$this->assertEqualsWithDelta( time(), $creditCardPayment->getValuationDate()->getTimestamp(), 5 );
	}

	public function testGivenBookedPayment_paymentLegacyDataIsNonEmptyArray(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$creditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$legacyData = $creditCardPayment->getLegacyData();
		$this->assertNotEmpty( $legacyData->paymentSpecificValues );
		// spot-check some values to see if we have the right field names
		$this->assertSame( '1337', $legacyData->paymentSpecificValues['mcp_amount'] );
		$this->assertSame( 'customer.prefix-ID2tbnag4a9u', $legacyData->paymentSpecificValues['ext_payment_id'] );
		$this->assertSame( 'e20fb9d5281c1bca1901c19f6e46213191bb4c17', $legacyData->paymentSpecificValues['ext_payment_account'] );
		$this->assertNotEmpty( $legacyData->paymentSpecificValues['ext_payment_timestamp'] );
	}

	public function testGivenNewPayment_paymentLegacyDataIsEmptyArray(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$this->assertSame( [],  $creditCardPayment->getLegacyData()->paymentSpecificValues );
	}
}
