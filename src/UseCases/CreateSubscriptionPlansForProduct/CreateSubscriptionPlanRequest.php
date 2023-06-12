<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

class CreateSubscriptionPlanRequest {

	/**
	 * @param string $productName
	 * @param string $id
	 * @param int[] $intervals
	 */
	public function __construct(
		public readonly string $productName,
		public readonly string $id,
		public readonly array $intervals
	) {
	}
}
