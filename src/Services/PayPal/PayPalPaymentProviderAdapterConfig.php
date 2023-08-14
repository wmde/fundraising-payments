<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

/**
 * Configuration values for the PayPal API requests with information shared across all requests
 * but not provided in individual payment requests.
 *
 * Loaded from a configuration file with entries for each language and product.
 * Example: With 2 products (memberships and donations) and 2 languages (de and en)
 * there will be 4 different PayPalAPIConfig instances.
 */
class PayPalPaymentProviderAdapterConfig {

	/**
	 * @param string $productName
	 * @param string $returnURL
	 * @param string $cancelURL
	 * @param array<string,SubscriptionPlan> $subscriptionPlanMap (PaymentInterval names ("Monthly", "HalfYearly") as keys)
	 */
	public function __construct(
		public readonly string $productName,
		public readonly string $returnURL,
		public readonly string $cancelURL,
		public readonly array $subscriptionPlanMap
	) {
	}
}
