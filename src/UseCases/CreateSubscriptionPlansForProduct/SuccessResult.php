<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

class SuccessResult {

	public function __construct(
		public readonly Product $successfullyCreatedProduct,
		public readonly bool $productAlreadyExisted,
		public readonly SubscriptionPlan $successfullyCreatedSubscriptionPlan,
		public readonly bool $subscriptionPlanAlreadyExisted,
	) {
	}

}
