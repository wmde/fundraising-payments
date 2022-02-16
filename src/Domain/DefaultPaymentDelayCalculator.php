<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain;

use DateInterval;
use DateTime;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class DefaultPaymentDelayCalculator implements PaymentDelayCalculator {

	private int $paymentDelayInDays;

	public function __construct( int $paymentDelayInDays ) {
		$this->paymentDelayInDays = $paymentDelayInDays;
	}

	public function calculateFirstPaymentDate( string $baseDate = '' ): DateTime {
		$date = new DateTime( $baseDate );
		$date->add( new DateInterval( 'P' . $this->paymentDelayInDays . 'D' ) );
		return $date;
	}
}
