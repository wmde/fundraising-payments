<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

class PayPalAPIConfig {

	/**
	 * @param string $productName
	 * @param string $returnURL
	 * @param string $cancelURL
	 * @param array<string,SubscriptionPlan> $subscriptionPlanMap (see subscriptionplan_map.json )
	 */
	public function __construct(
		public readonly string $productName,
		public readonly string $returnURL,
		public readonly string $cancelURL,
		public readonly array $subscriptionPlanMap
	) {
	}
}
