<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Data;

class PayPalPaymentBookingData {

	public const PAYMENT_DATE = '10:54:49 Dec 02, 2012 PST';
	public const TRANSACTION_ID = 'T4242';

	/**
	 * @return array<string,mixed>
	 */
	public static function newValidBookingData(): array {
		return [
			'address_city' => 'Chicago',
			'address_country_code' => 'US of EEHHH',
			'address_name' => 'Joe Dirt',
			'address_status' => 'Upside Down',
			'address_street' => 'Sesame',
			'address_zip' => '666',
			'first_name' => 'Joe',
			'item_number' => 1,
			'last_name' => 'Dirt',
			'mc_currency' => 'EUR',
			'mc_fee' => '2.70',
			'mc_gross' => '2.70',
			'payer_email' => 'foerderpp@wikimedia.de',
			'payer_id' => '42',
			'payer_status' => 'verified',
			'payment_date' => self::PAYMENT_DATE,
			'payment_status' => 'processed',
			'payment_type' => 'instant',
			'settle_amount' => '2.70',
			'subscr_id' => '8RHHUM3W3PRH7QY6B59',
			'txn_id' => self::TRANSACTION_ID,
			'txn_type' => 'express_checkout',
		];
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function newValidFollowupBookingData(): array {
		return [
			...self::newValidBookingData(),
			'txn_id' => '4243'
		];
	}

	public static function newEncodedValidBookingData(): string {
		return '{"item_number":"1","mc_currency":"EUR","mc_fee":"2.70","mc_gross":"2.70","payer_email":"foerderpp@wikimedia.de","payer_id":"42","payer_status":"verified","payment_date":"10:54:49 Dec 02, 2012 PST","payment_status":"processed","payment_type":"instant","settle_amount":"2.70","subscr_id":"8RHHUM3W3PRH7QY6B59","txn_id":"T4242","txn_type":"express_checkout"}';
	}
}
