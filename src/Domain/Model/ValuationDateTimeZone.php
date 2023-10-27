<?php

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

class ValuationDateTimeZone {

	private const TIMEZONE = 'UTC';

	public static function getTimeZone(): \DateTimeZone {
		return new \DateTimeZone( self::TIMEZONE );
	}
}
