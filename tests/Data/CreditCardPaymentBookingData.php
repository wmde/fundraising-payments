<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Data;

class CreditCardPaymentBookingData {

	/**
	 * @param int $amount
	 * @return array<string,scalar>
	 */
	public static function newValidBookingData( int $amount = 100_000 ): array {
		return [
			'function' => 'billing',
			'donation_id' => 1,
			'amount' => $amount,
			'transactionId' => 'customer.prefix-ID2tbnag4a9u',
			'customerId' => 'e20fb9d5281c1bca1901c19f6e46213191bb4c17',
			'sessionId' => 'CC13064b2620f4028b7d340e3449676213336a4d',
			'auth' => 'd1d6fae40cf96af52477a9e521558ab7',
			'utoken' => 'my_secret_update_token',
			'token' => 'my_secret_access_token',
			'title' => 'Your generous donation',
			'country' => 'DE',
			'currency' => 'EUR',
		];
	}
}
