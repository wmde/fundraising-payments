<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * Used to generate the deprecated payment status for bookable payments
 *
 * @see LegacyPaymentStatus
 * @see Payment::getLegacyData()
 * @deprecated We can remove this trait when https://phabricator.wikimedia.org/T281853
 *             (removal of status from donation model) is done
 */
trait LegacyBookingStatusTrait {

	abstract public function isBooked(): bool;

	protected function getLegacyPaymentStatus(): string {
		if ( $this->isBooked() ) {
			return LegacyPaymentStatus::EXTERNAL_BOOKED->value;
		}
		return LegacyPaymentStatus::EXTERNAL_INCOMPLETE->value;
	}

}
