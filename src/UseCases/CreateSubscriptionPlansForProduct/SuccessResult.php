<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;

class SuccessResult {

	/**
	 * @param Product[] $successfullyCreatedProducts
	 * @param Product[] $alreadyExistingProducts
	 */
	public function __construct(
		public readonly array $successfullyCreatedProducts,
		public readonly array $alreadyExistingProducts
	) {
	}

}
