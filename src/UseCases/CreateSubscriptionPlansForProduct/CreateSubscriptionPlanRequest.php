<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class CreateSubscriptionPlanRequest {

	/**
	 * @param string $productName
	 * @param string $id //TODO this is probably the product ID? add documentation
	 * @param PaymentInterval $interval
	 */
	public function __construct(
		public readonly string $productName,
		public readonly string $id,
		public readonly PaymentInterval $interval
	) {
		if ( $this->interval->value < 1 ) {
			throw new \UnexpectedValueException( "Interval must be bigger than 0 (recurring)!" );
		}
		// TODO all intervals are allowed except "one time" 0
		//TODO write test for that invalid value
	}
}
