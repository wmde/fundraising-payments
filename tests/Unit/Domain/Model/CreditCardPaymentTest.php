<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\LegacyPaymentStatus;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Tests\Data\CreditCardPaymentBookingData;
use WMDE\Fundraising\PaymentContext\Tests\Fixtures\DummyPaymentIdRepository;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment
 */
class CreditCardPaymentTest extends TestCase {

	private const OTHER_TRANSACTION_ID = '3388998877';

	public function testNewCreditCardPaymentsAreNotBookedAndIncomplete(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$this->assertTrue( $creditCardPayment->canBeBooked( [] ) );
		$this->assertFalse( $creditCardPayment->isCompleted() );
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

	public function testBookPaymentWithMismatchedAmountFails(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$this->expectException( \UnexpectedValueException::class );
		$this->expectExceptionMessageMatches( '/amount/' );

		$creditCardPayment->bookPayment(
			[
				...CreditCardPaymentBookingData::newValidBookingData(),
				'transactionId' => self::OTHER_TRANSACTION_ID,
				'amount' => '1337'
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
		$this->assertTrue( $creditCardPayment->isCompleted() );
	}

	public function testGivenBookedPayment_paymentLegacyDataIsNonEmptyArray(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$creditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$legacyData = $creditCardPayment->getLegacyData();
		$this->assertNotEmpty( $legacyData->paymentSpecificValues );
		// spot-check some values to see if we have the right field names
		$this->assertSame( '100000', $legacyData->paymentSpecificValues['mcp_amount'] );
		$this->assertSame( 'customer.prefix-ID2tbnag4a9u', $legacyData->paymentSpecificValues['ext_payment_id'] );
		$this->assertSame( 'e20fb9d5281c1bca1901c19f6e46213191bb4c17', $legacyData->paymentSpecificValues['ext_payment_account'] );
		$this->assertNotEmpty( $legacyData->paymentSpecificValues['ext_payment_timestamp'] );
	}

	public function testGivenNewPayment_paymentLegacyDataIsEmptyArray(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );

		$this->assertSame( [],  $creditCardPayment->getLegacyData()->paymentSpecificValues );
	}

	public function testStatusInLegacyDataChangesWithBookedStatus(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$bookedCreditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$bookedCreditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$this->assertSame( LegacyPaymentStatus::EXTERNAL_INCOMPLETE->value, $creditCardPayment->getLegacyData()->paymentStatus );
		$this->assertSame( LegacyPaymentStatus::EXTERNAL_BOOKED->value, $bookedCreditCardPayment->getLegacyData()->paymentStatus );
	}

	public function testGetDisplayDataReturnsAllFieldsToDisplayForBookedPayment(): void {
		$creditCardPayment = new CreditCardPayment( 1, Euro::newFromInt( 1000 ), PaymentInterval::Monthly );
		$creditCardPayment->bookPayment( CreditCardPaymentBookingData::newValidBookingData(), new DummyPaymentIdRepository() );

		$expectedValues = [
			'amount' => 100000,
			'interval' => 1,
			'paymentType' => 'MCP',
			'ext_payment_id' => 'customer.prefix-ID2tbnag4a9u',
			'mcp_amount' => '100000',
			'ext_payment_account' => 'e20fb9d5281c1bca1901c19f6e46213191bb4c17',
			'mcp_sessionid' => 'CC13064b2620f4028b7d340e3449676213336a4d',
			'mcp_auth' => 'd1d6fae40cf96af52477a9e521558ab7',
			'mcp_title' => 'Your generous donation',
			'mcp_country' => 'DE',
			'mcp_currency' => 'EUR',
			'mcp_cc_expiry_date' => '',
			'ext_payment_status' => 'processed'
		];

		$actualDisplayData = $creditCardPayment->getDisplayValues();

		$this->assertNotNull( $creditCardPayment->getValuationDate() );
		$this->assertFalse( $creditCardPayment->canBeBooked( [] ) );
		unset( $actualDisplayData['ext_payment_timestamp'] );
		$this->assertEquals( $expectedValues, $actualDisplayData );
	}
}
