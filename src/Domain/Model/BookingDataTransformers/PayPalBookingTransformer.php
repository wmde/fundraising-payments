<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers;

class PayPalBookingTransformer {

	private const PAYER_ID_KEY = 'payer_id';
	private const VALUATION_DATE_KEY = 'payment_date';
	public const TRANSACTION_ID_KEY = 'txn_id';
	public const TRANSACTION_TYPE_KEY = 'txn_type';
	public const PAYMENT_STATUS_LEGACY_KEY = 'ext_payment_status';

	/**
	 * Sent by PayPal in "payment_date" field.
	 *
	 * Example: 10:54:49 Dec 02, 2012 PST
	 */
	public const PAYPAL_DATE_FORMAT = "H:i:s M d, Y e";

	private const KEYS_TO_FILTER = [
		'first_name',
		'last_name',
		'address_name',
		'address_street',
		'address_status',
		'address_zip',
		'address_city',
		'address_country_code',
	];

	private const LEGACY_KEY_MAP = [
		'paypal_payer_id' => 'payer_id',
		'paypal_subscr_id' => 'subscr_id',
		'paypal_payer_status' => 'payer_status',
		'paypal_mc_gross' => 'mc_gross',
		'paypal_mc_currency' => 'mc_currency',
		'paypal_mc_fee' => 'mc_fee',
		'paypal_settle_amount' => 'settle_amount',
		'ext_payment_id' => 'txn_id',
		'ext_subscr_id' => 'subscr_id',
		'ext_payment_type' => 'payment_type',
		'ext_payment_status' => 'payment_status',
		'ext_payment_account' => 'payer_id',
		'ext_payment_timestamp' => 'payment_date',
	];

	/**
	 * @var array<string,mixed>
	 */
	private array $rawBookingData;

	private string $transactionId;

	private \DateTimeImmutable $valuationDate;

	/**
	 * @param array<string,mixed> $rawBookingData
	 */
	public function __construct( array $rawBookingData ) {
		if ( empty( $rawBookingData[self::PAYER_ID_KEY] ) ) {
			throw new \InvalidArgumentException( 'Transaction data must have payer ID' );
		}
		if ( empty( $rawBookingData[self::VALUATION_DATE_KEY] ) ) {
			throw new \InvalidArgumentException( 'Transaction data must have a valuation date' );
		}
		if ( empty( $rawBookingData[self::TRANSACTION_ID_KEY] ) ) {
			throw new \InvalidArgumentException( 'Transaction data must have transaction ID' );
		}

		$valuationDate = \DateTimeImmutable::createFromFormat( self::PAYPAL_DATE_FORMAT, strval( $rawBookingData[self::VALUATION_DATE_KEY] ) );

		if ( !$valuationDate ) {
			throw new \InvalidArgumentException( sprintf(
				'Transaction data must contain valid valuation date, format "%s"',
				self::PAYPAL_DATE_FORMAT
			) );
		}

		$this->valuationDate = $valuationDate;
		$this->transactionId = strval( $rawBookingData[self::TRANSACTION_ID_KEY] );
		$this->rawBookingData = $this->anonymise( $rawBookingData );
	}

	/**
	 * @return array<string,string>
	 */
	public function getBookingData(): array {
		return array_map( 'strval', $this->rawBookingData );
	}

	public function getValuationDate(): \DateTimeImmutable {
		return $this->valuationDate;
	}

	/**
	 * @return array<string,mixed>
	 */
	public function getLegacyData(): array {
		$result = [];
		foreach ( self::LEGACY_KEY_MAP as $legacyKey => $bookingDataKey ) {
			$result[$legacyKey] = $this->rawBookingData[$bookingDataKey] ?? '';
		}

		if ( isset( $result[self::PAYMENT_STATUS_LEGACY_KEY] ) && isset( $this->rawBookingData[self::TRANSACTION_TYPE_KEY] ) ) {
			$result[self::PAYMENT_STATUS_LEGACY_KEY] = $result[self::PAYMENT_STATUS_LEGACY_KEY] . '/' . $this->rawBookingData[self::TRANSACTION_TYPE_KEY];
		}

		return $result;
	}

	/**
	 * @param array<string,mixed> $rawBookingData
	 *
	 * @return array<string,mixed>
	 */
	private function anonymise( array $rawBookingData ): array {
		return array_diff_key( $rawBookingData, array_flip( self::KEYS_TO_FILTER ) );
	}

	public function getTransactionId(): string {
		return $this->transactionId;
	}

}
