<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

trait LegacyBookingStatusTrait {

	abstract public function isBooked(): bool;

	protected function getLegacyPaymentStatus(): string {
		if ( $this->isBooked() ) {
			return LegacyPaymentStatus::EXTERNAL_BOOKED->value;
		}
		return LegacyPaymentStatus::EXTERNAL_INCOMPLETE->value;
	}

}
