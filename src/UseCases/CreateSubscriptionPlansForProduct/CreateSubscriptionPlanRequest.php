<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class CreateSubscriptionPlanRequest {

	/**
	 * @param string $productId
	 * @param string $productName
	 * @param PaymentInterval $interval
	 */
	public function __construct(
		public readonly string $productId,
		public readonly string $productName,
		public readonly PaymentInterval $interval
	) {
		if ( !$this->interval->isRecurring() ) {
			throw new \UnexpectedValueException( "Interval must be recurring" );
		}
	}
}
