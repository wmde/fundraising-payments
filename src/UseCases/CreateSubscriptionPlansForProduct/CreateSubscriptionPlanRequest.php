<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class CreateSubscriptionPlanRequest {

	/**
	 * @param string $productName
	 * @param string $productId
	 * @param PaymentInterval $interval
	 */
	public function __construct(
		public readonly string $productName,
		public readonly string $productId,
		public readonly PaymentInterval $interval
	) {
		if ( $this->interval === PaymentInterval::OneTime ) {
			throw new \UnexpectedValueException( "Interval must be recurring" );
		}
	}
}
