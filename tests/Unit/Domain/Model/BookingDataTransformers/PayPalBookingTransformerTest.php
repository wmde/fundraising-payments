<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Domain\Model\BookingDataTransformers;

use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\PayPalBookingTransformer;
use WMDE\Fundraising\PaymentContext\Tests\Data\PayPalPaymentBookingData;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers\PayPalBookingTransformer
 */
class PayPalBookingTransformerTest extends TestCase {

	public function testAnonymisesRawBookingData(): void {
		$transformer = new PayPalBookingTransformer( PayPalPaymentBookingData::newValidBookingData() );
		$bookingData = $transformer->getBookingData();

		$this->assertArrayNotHasKey( 'first_name', $bookingData );
		$this->assertArrayNotHasKey( 'last_name', $bookingData );
		$this->assertArrayNotHasKey( 'address_name', $bookingData );
		$this->assertArrayNotHasKey( 'address_street', $bookingData );
		$this->assertArrayNotHasKey( 'address_status', $bookingData );
		$this->assertArrayNotHasKey( 'address_zip', $bookingData );
		$this->assertArrayNotHasKey( 'address_city', $bookingData );
		$this->assertArrayNotHasKey( 'address_country_code', $bookingData );
	}

	/**
	 * @dataProvider invalidBookingDataProvider
	 *
	 * @param array<mixed> $transactionData
	 * @return void
	 */
	public function testGivenMissingFields_constructorThrowsException( array $transactionData ): void {
		$this->expectException( \InvalidArgumentException::class );

		new PayPalBookingTransformer( $transactionData );
	}

	/**
	 * @return iterable<mixed>
	 */
	public function invalidBookingDataProvider(): iterable {
		yield 'empty valuation date' => [ [ 'payer_id' => 72, 'txn_id' => PayPalPaymentBookingData::TRANSACTION_ID ] ];
		yield 'empty payer ID' => [ [ 'payment_date' => PayPalPaymentBookingData::PAYMENT_DATE, 'txn_id' => PayPalPaymentBookingData::TRANSACTION_ID ] ];
		yield 'empty transaction ID' => [ [ 'payer_id' => 72, 'payment_date' => PayPalPaymentBookingData::PAYMENT_DATE ] ];
	}

	/** @dataProvider invalidValuationDateProvider */
	public function testGivenInvalidValuationDate_ItThrowsException( mixed $invalidValuationDate ): void {
		$this->expectException( \InvalidArgumentException::class );

		new PayPalBookingTransformer( [ 'payer_id' => 1, 'payment_date' => $invalidValuationDate ] );
	}

	/**
	 * @return iterable<mixed>
	 */
	public function invalidValuationDateProvider(): iterable {
		yield [ 0 ];
		yield [ '' ];
		yield [ 'Not a date' ];
		yield [ -1 ];
	}

	public function testGetValuationDate(): void {
		$transformer = new PayPalBookingTransformer( PayPalPaymentBookingData::newValidBookingData() );

		$this->assertEquals( new \DateTimeImmutable( PayPalPaymentBookingData::PAYMENT_DATE ), $transformer->getValuationDate() );
	}

	public function testGetTransactionId(): void {
		$transformer = new PayPalBookingTransformer( PayPalPaymentBookingData::newValidBookingData() );

		$this->assertSame( PayPalPaymentBookingData::TRANSACTION_ID, $transformer->getTransactionId() );
	}

	public function testLegacyFieldsGetTransformed(): void {
		$bookingRequestData = PayPalPaymentBookingData::newValidBookingData();
		$transformer = new PayPalBookingTransformer( $bookingRequestData );

		$result = $transformer->getLegacyData();

		$this->assertEquals( [
			'paypal_payer_id' => $bookingRequestData['payer_id'],
			'paypal_subscr_id' => $bookingRequestData['subscr_id'],
			'paypal_payer_status' => $bookingRequestData['payer_status'],
			'paypal_mc_gross' => $bookingRequestData['mc_gross'],
			'paypal_mc_currency' => $bookingRequestData['mc_currency'],
			'paypal_mc_fee' => $bookingRequestData['mc_fee'],
			'paypal_settle_amount' => $bookingRequestData['settle_amount'],
			'ext_payment_id' => $bookingRequestData['txn_id'],
			'ext_subscr_id' => $bookingRequestData['subscr_id'],
			'ext_payment_type' => $bookingRequestData['payment_type'],
			'ext_payment_status' => $bookingRequestData['payment_status'],
			'ext_payment_account' => $bookingRequestData['payer_id'],
			'ext_payment_timestamp' => $bookingRequestData['payment_date'],
		], $result );
	}
}
