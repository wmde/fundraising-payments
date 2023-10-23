<?php

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Order;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

interface PaypalAPI {

	/**
	 * @see https://developer.paypal.com/docs/api/catalog-products/v1/#products_list
	 * @return Product[]
	 */
	public function listProducts(): array;

	/**
	 * @see https://developer.paypal.com/docs/api/catalog-products/v1/#products_create
	 */
	public function createProduct( Product $product ): Product;

	/**
	 * @see https://developer.paypal.com/docs/api/subscriptions/v1/#plans_list
	 * @param string $productId
	 * @return SubscriptionPlan[]
	 */
	public function listSubscriptionPlansForProduct( string $productId ): array;

	/**
	 * @see https://developer.paypal.com/docs/api/subscriptions/v1/#plans_create
	 */
	public function createSubscriptionPlanForProduct( SubscriptionPlan $subscriptionPlan ): SubscriptionPlan;

	/**
	 * @see https://developer.paypal.com/docs/api/subscriptions/v1/#subscriptions_create
	 */
	public function createSubscription( SubscriptionParameters $subscriptionParameters ): Subscription;

	/**
	 * @see https://developer.paypal.com/docs/api/orders/v2/#orders_create
	 */
	public function createOrder( OrderParameters $orderParameters ): Order;

}
