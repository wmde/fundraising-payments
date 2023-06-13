<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

class SuccessResult {

	/**
	 * @param Product $successfullyCreatedProduct
	 * @param bool $productAlreadyExisted
	 * @param SubscriptionPlan|null $successfullyCreatedSubscriptionPlan
	 * @param bool|null $subscriptionPlanAlreadyExisted
	 */
	public function __construct(
		public readonly Product $successfullyCreatedProduct,
		public readonly bool $productAlreadyExisted,
		public readonly ?SubscriptionPlan $successfullyCreatedSubscriptionPlan = null,
		public readonly ?bool $subscriptionPlanAlreadyExisted = null,
	) {
	}

}
