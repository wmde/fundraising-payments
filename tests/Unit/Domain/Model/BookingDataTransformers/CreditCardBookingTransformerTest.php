<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model\BookingDataTransformers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\CreditCardBookingTransformer;
use WMDE\Fundraising\PaymentContext\Domain\Model\ValuationDateTimeZone;

#[CoversClass( \WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\CreditCardBookingTransformer::class )]
#[CoversClass( \WMDE\Fundraising\PaymentContext\Domain\Model\ValuationDateTimeZone::class )]
class CreditCardBookingTransformerTest extends TestCase {

	public function testGetBookingDataReturnsArrayOfStrings(): void {
		$transformer = new CreditCardBookingTransformer( [
			'transactionId' => 1,
			'amount' => 123,
			'sessionId' => 'deadbeef'
		] );

		$result = $transformer->getBookingData();

		$this->assertSame(
			[
				'transactionId' => '1',
				'amount' => '123',
				'sessionId' => 'deadbeef'
			],
			$result
		);
	}

	public function testGetTransactionIdReturnsTransactionId(): void {
		$transformer = new CreditCardBookingTransformer( [
			'transactionId' => 1,
			'amount' => 123,
			'sessionId' => 'deadbeef'
		] );

		$this->assertSame( '1', $transformer->getTransactionId() );
	}

	public function testGetAmountReturnsAmountObject(): void {
		$transformer = new CreditCardBookingTransformer( [
			'transactionId' => 1,
			'amount' => 123,
			'sessionId' => 'deadbeef'
		] );

		$this->assertEquals( Euro::newFromCents( 123 ), $transformer->getAmount() );
	}

	public function testGivenNoValuationDateAndGetValuationDateReturnsCurrentDateTime(): void {
		$transformer = new CreditCardBookingTransformer( [
			'transactionId' => 1,
			'amount' => 123,
			'sessionId' => 'deadbeef'
		] );

		$this->assertEqualsWithDelta( time(), $transformer->getValuationDate()->getTimestamp(), 5 );
	}

	public function testGivenNoValuationDateAndGetValuationDateDateTimeInUTCTimeZone(): void {
		$transformer = new CreditCardBookingTransformer( [
			'transactionId' => 1,
			'amount' => 123,
			'sessionId' => 'deadbeef'
		] );

		$this->assertEqualsCanonicalizing( ValuationDateTimeZone::getTimeZone(), $transformer->getValuationDate()->getTimezone() );
	}

	public function testGivenValuationDateAndGetValuationDateReturnsValuationDate(): void {
		$valuationDate = new \DateTimeImmutable( '2023-11-06 0:00:00', new \DateTimeZone( 'Europe/Berlin' ) );
		$expectedValuationDate = new \DateTimeImmutable( '2023-11-05 23:00:00', ValuationDateTimeZone::getTimeZone() );
		$transformer = new CreditCardBookingTransformer( [
			'transactionId' => 1,
			'amount' => 123,
			'sessionId' => 'deadbeef'
		], $valuationDate );

		$this->assertEquals( ValuationDateTimeZone::getTimeZone(), $transformer->getValuationDate()->getTimezone() );
		$this->assertEquals( $expectedValuationDate, $transformer->getValuationDate() );
	}

	public function testGivenBadBookingDataThrowsError(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->expectExceptionMessage( 'transactionId was not provided' );

		new CreditCardBookingTransformer( [
			'amount' => 123,
			'sessionId' => 'deadbeef'
		] );
	}

	public function testLegacyFieldsGetTransformed(): void {
		$transformer = new CreditCardBookingTransformer( [
			'transactionId' => 1,
			'amount' => 123,
			'sessionId' => 'deadbeef',
			'country' => 'de',
			'currency' => 'EUR',
			'auth' => 'my-auth-key',
			'title' => 'Your donation for free knowledge',
			'customerId' => 4711,
			'expiryDate' => '2085/02',
			'some random value' => '99, this should not be in result',
		], new \DateTimeImmutable( '1984/12/12' ) );

		$result = $transformer->getLegacyData();

		$this->assertEquals(
			[
				'ext_payment_id' => 1,
				'ext_payment_status' => 'processed',
				'ext_payment_timestamp' => '1984-12-12T00:00:00+00:00',
				'mcp_amount' => 123,
				'ext_payment_account' => 4711,
				'mcp_sessionid' => 'deadbeef',
				'mcp_auth' => 'my-auth-key',
				'mcp_title' => 'Your donation for free knowledge',
				'mcp_country' => 'de',
				'mcp_currency' => 'EUR',
				'mcp_cc_expiry_date' => '2085/02'
			],
			$result
		);
	}

}
