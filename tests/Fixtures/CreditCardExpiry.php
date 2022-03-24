<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

class CreditCardExpiry {

	private int $month;
	private int $year;

	public function __construct( int $month, int $year ) {
		if ( $month < 1 || $month > 12 ) {
			throw new \InvalidArgumentException( '$month needs to be between 1 and 12' );
		}

		$this->month = $month;
		$this->year = $year;
	}

	public static function newFromString( string $expirationDate ): ?self {
		$dateParts = explode( '/', $expirationDate );
		if ( count( $dateParts ) === 2 ) {
			return new self( (int)$dateParts[0], (int)$dateParts[1] );
		}

		return null;
	}

	public function getMonth(): int {
		return $this->month;
	}

	public function getYear(): int {
		return $this->year;
	}

}
