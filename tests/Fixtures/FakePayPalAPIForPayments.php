<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Order;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PaypalAPI;

/**
 * This API implementation is for tests that call "createOrder" and "createSubscription" methods
 *
 * Use {@see FakePayPalAPIForSetup} in tests that need the API to set up subscription plans and products for the subscription plans
 */
class FakePayPalAPIForPayments implements PaypalAPI {

	/**
	 * @var SubscriptionParameters[]
	 */
	private array $subscriptionParameters = [];

	/**
	 * @var OrderParameters[]
	 */
	private array $orderParameters = [];

	/**
	 * @param Subscription[] $subscriptions
	 * @param Order[] $orders
	 */
	public function __construct(
		private array $subscriptions = [],
		private array $orders = [],
	) {
	}

	/**
	 * @return Product[]
	 */
	public function listProducts(): array {
		throw new \LogicException( 'Not implemented yet, your tests should not use it' );
	}

	public function createProduct( Product $product ): Product {
		throw new \LogicException( 'Not implemented yet, your tests should not use it' );
	}

	public function hasProduct( Product $product ): bool {
		throw new \LogicException( 'Not implemented yet, your tests should not use it' );
	}

	public function hasSubscriptionPlan( SubscriptionPlan $subscriptionPlan ): bool {
		throw new \LogicException( 'Not implemented yet, your tests should not use it' );
	}

	/**
	 * @return SubscriptionPlan[]
	 */
	public function listSubscriptionPlansForProduct( string $productId ): array {
		throw new \LogicException( 'Not implemented yet, your tests should not use it' );
	}

	public function createSubscriptionPlanForProduct( SubscriptionPlan $subscriptionPlan ): SubscriptionPlan {
		throw new \LogicException( 'Not implemented yet, your tests should not use it' );
	}

	public function createSubscription( SubscriptionParameters $subscriptionParameters ): Subscription {
		$subscription = current( $this->subscriptions );
		if ( $subscription === false ) {
			throw new \OutOfBoundsException( 'Your test setup did not add enough subscriptions to the fake API implementation' );
		}
		$this->subscriptionParameters[] = $subscriptionParameters;
		next( $this->subscriptions );
		return $subscription;
	}

	public function createOrder( OrderParameters $orderParameters ): Order {
		$order = current( $this->orders );
		if ( $order === false ) {
			throw new \OutOfBoundsException( 'Your test setup did not add enough orders to the fake API implementation' );
		}
		$this->orderParameters[] = $orderParameters;
		next( $this->orders );
		return $order;
	}

	/**
	 * @return SubscriptionParameters[]
	 */
	public function getSubscriptionParameters(): array {
		return $this->subscriptionParameters;
	}

	/**
	 * @return OrderParameters[]
	 */
	public function getOrderParameters(): array {
		return $this->orderParameters;
	}

}
