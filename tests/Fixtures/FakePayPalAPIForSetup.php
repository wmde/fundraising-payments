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
 * This API implementation is for tests that call the "setup" functionality of the PayPal API
 * (creating subscription plans and products for the subscription plans).
 *
 * Use {@see FakePayPalAPIForPayments} in tests that need the API to create payments
 */
class FakePayPalAPIForSetup implements PaypalAPI {

	public const GENERATED_ID = 'P-GENERATED';

	/**
	 * @var Product[]
	 */
	private array $products = [];

	/**
	 * @var array<string,array<int,SubscriptionPlan>>
	 */
	private array $subscriptionPlans = [];

	/**
	 * @param Product[] $products
	 * @param SubscriptionPlan[] $subscriptionPlans
	 */
	public function __construct(
		array $products = [],
		array $subscriptionPlans = []
	) {
		foreach ( $products as $product ) {
			$this->createProduct( $product );
		}
		foreach ( $subscriptionPlans as $subscriptionPlan ) {
			$this->createSubscriptionPlanForProduct( $subscriptionPlan );
		}
	}

	/**
	 * @return Product[]
	 */
	public function listProducts(): array {
		return $this->products;
	}

	public function createProduct( Product $product ): Product {
		$this->products[ $product->id ] = $product;
		return $product;
	}

	public function hasProduct( Product $product ): bool {
		if ( empty( $this->products[ $product->id ] ) ) {
			return false;
		} else {
			// compare by value, not by reference
			return $this->products[ $product->id ] == $product;
		}
	}

	public function hasSubscriptionPlan( SubscriptionPlan $subscriptionPlan ): bool {
		if ( empty( $this->subscriptionPlans[ $subscriptionPlan->productId ][$subscriptionPlan->monthlyInterval->value] ) ) {
			return false;
		}
		$subscriptionPlanFromStorage = $this->subscriptionPlans[ $subscriptionPlan->productId ][$subscriptionPlan->monthlyInterval->value];
		return $subscriptionPlanFromStorage->productId === $subscriptionPlan->productId &&
			$subscriptionPlanFromStorage->monthlyInterval === $subscriptionPlan->monthlyInterval;
	}

	/**
	 * @return SubscriptionPlan[]
	 */
	public function listSubscriptionPlansForProduct( string $productId ): array {
		return $this->subscriptionPlans[$productId] ?? [];
	}

	public function createSubscriptionPlanForProduct( SubscriptionPlan $subscriptionPlan ): SubscriptionPlan {
		$storedSubscriptionPlan = new SubscriptionPlan( $subscriptionPlan->name, $subscriptionPlan->productId, $subscriptionPlan->monthlyInterval, self::GENERATED_ID );
		if ( empty( $this->subscriptionPlans[$subscriptionPlan->productId] ) ) {
			$this->subscriptionPlans[$subscriptionPlan->productId] = [ $subscriptionPlan->monthlyInterval->value => $storedSubscriptionPlan ];
		} else {
			$this->subscriptionPlans[ $subscriptionPlan->productId ][$subscriptionPlan->monthlyInterval->value] = $storedSubscriptionPlan;
		}
		return $storedSubscriptionPlan;
	}

	public function createSubscription( SubscriptionParameters $subscriptionParameters ): Subscription {
		throw new \LogicException( 'Not implemented yet, your tests should not use it' );
	}

	public function createOrder( OrderParameters $orderParameters ): Order {
		throw new \LogicException( 'Not implemented yet, your tests should not use it' );
	}

}
