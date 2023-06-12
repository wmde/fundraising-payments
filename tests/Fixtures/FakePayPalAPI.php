<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;

class FakePayPalAPI implements PaypalAPI {
	/**
	 * @param Product[] $products
	 * @param SubscriptionPlan[] $subscriptionPlans
	 */
	public function __construct(
		private array $products = [],
		private array $subscriptionPlans = []
	) {
	}

	public function listProducts(): array {
		return $this->products;
	}

	public function createProduct( Product $product ): Product {
		$this->products[] = $product;
		return $product;
	}

	public function listSubscriptionPlansForProduct( string $productId ): array {
		return $this->subscriptionPlans;
	}

	/**
	 * @return Product[]
	 */
	public function getProducts(): array {
		return $this->products;
	}

	/**
	 * @return SubscriptionPlan[]
	 */
	public function getSubscriptionPlans(): array {
		return $this->subscriptionPlans;
	}

}
