<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

enum PaymentInterval: int {
	case OneTime = 0;
	case Monthly = 1;
	case Quarterly = 3;
	case HalfYearly = 6;
	case Yearly = 12;

	private const ALLOWED_INTERVALS = [
		'OneTime' => PaymentInterval::OneTime,
		'Monthly' => PaymentInterval::Monthly,
		'Quarterly' => PaymentInterval::Quarterly,
		'HalfYearly' => PaymentInterval::HalfYearly,
		'Yearly' => PaymentInterval::Yearly,
	];

	public function isRecurring(): bool {
		return $this !== self::OneTime;
	}

	public static function fromString( string $interval ): self {
		if ( isset( self::ALLOWED_INTERVALS[ $interval ] ) ) {
			return self::ALLOWED_INTERVALS[ $interval ];
		} else {
			throw new \OutOfBoundsException( 'Invalid payment interval given' );
		}
	}
}
