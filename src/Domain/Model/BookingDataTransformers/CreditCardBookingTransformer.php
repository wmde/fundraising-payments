<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model\BookingDataTransformers;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\ValuationDateTimeZone;

class CreditCardBookingTransformer {

	private const TRANSACTION_ID_KEY = 'transactionId';
	private const AMOUNT_KEY = 'amount';

	private const LEGACY_KEY_MAP = [
		'ext_payment_id' => 'transactionId',
		'mcp_amount' => 'amount',
		'ext_payment_account' => 'customerId',
		'mcp_sessionid' => 'sessionId',
		'mcp_auth' => 'auth',
		'mcp_title' => 'title',
		'mcp_country' => 'country',
		'mcp_currency' => 'currency',
		'mcp_cc_expiry_date' => 'expiryDate'
	];

	/**
	 * @var array<string, scalar>
	 */
	private array $rawBookingData;

	private \DateTimeImmutable $valuationDate;

	/**
	 * @param array<string, scalar> $rawBookingData
	 * @param \DateTimeImmutable|null $valuationDate This parameter exists only for testing (and passing in a fixed time). The CreditCardPayment will always omit the parameter.
	 */
	public function __construct( array $rawBookingData, ?\DateTimeImmutable $valuationDate = null ) {
		$this->validateRawData( $rawBookingData );
		$this->rawBookingData = $rawBookingData;
		if ( $valuationDate === null ) {
			$this->valuationDate = new \DateTimeImmutable( 'now', ValuationDateTimeZone::getTimeZone() );
		} else {
			$this->valuationDate = $valuationDate->setTimezone( ValuationDateTimeZone::getTimeZone() );
		}
	}

	/**
	 * @return array<string, string>
	 */
	public function getBookingData(): array {
		return array_map( 'strval', $this->rawBookingData );
	}

	public function getTransactionId(): string {
		return strval( $this->rawBookingData[self::TRANSACTION_ID_KEY] );
	}

	/**
	 * @return Euro
	 * @throws \InvalidArgumentException
	 */
	public function getAmount(): Euro {
		return Euro::newFromCents( intval( $this->rawBookingData[self::AMOUNT_KEY] ) );
	}

	public function getValuationDate(): \DateTimeImmutable {
		return $this->valuationDate;
	}

	/**
	 * @param array<string, scalar> $rawBookingData
	 *
	 * @return void
	 */
	private function validateRawData( array $rawBookingData ): void {
		if ( empty( $rawBookingData[self::TRANSACTION_ID_KEY] ) ) {
			throw new \InvalidArgumentException( sprintf( "%s was not provided", self::TRANSACTION_ID_KEY ) );
		}
		if ( empty( $rawBookingData[self::AMOUNT_KEY] ) ) {
			throw new \InvalidArgumentException( sprintf( "%s was not provided", self::AMOUNT_KEY ) );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getLegacyData(): array {
		$result = [];
		foreach ( self::LEGACY_KEY_MAP as $legacyKey => $bookingDataKey ) {
			$result[$legacyKey] = $this->rawBookingData[$bookingDataKey] ?? '';
		}
		$result['ext_payment_status'] = 'processed';
		$result['ext_payment_timestamp'] = $this->getValuationDate()->format( \DateTimeInterface::ATOM );
		return $result;
	}

}
