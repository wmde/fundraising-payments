<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class CreateSubscriptionPlanRequest {

	/**
	 * @param string $productName
	 * @param string $id
	 * @param PaymentInterval $intervals
	 */
	public function __construct(
		public readonly string $productName,
		public readonly string $id,
		public readonly PaymentInterval $intervals
	) {
		// TODO all intervals are allowed except "one time" 0
		//TODO write test for that invalid value
	}
}
