<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

class PayPalAPIURLGeneratorConfigFactory {

	public static function createConfig( array $allConfigs, string $productKey, string $languageKey ): PayPalAPIURLGeneratorConfig {
		if ( !isset( $allConfigs[$productKey] ) ) {
			throw new \LogicException( "'$productKey' does not exist in PayPal API configuration. Please check your configuration file." );
		}

		if ( !isset( $allConfigs[$productKey][$languageKey] ) ) {
			throw new \LogicException( "'$languageKey' does not exist in PayPal API configuration for product '$productKey'. Please check your configuration file." );
		}

		$subconfig = $allConfigs[$productKey][$languageKey];
		$plans = self::createSubscriptionPlans( $subconfig['subscription_plans'], $subconfig['product_id'] );
		return new PayPalAPIURLGeneratorConfig(
			$subconfig['product_name'],
			$subconfig['return_url'],
			$subconfig['cancel_url'],
			$plans
		);
	}

	/**
	 * @param array{"interval":int, "name":string, "id":string}[] $subscriptionPlansConfig
	 * @param string $productId
	 * @return array<SubscriptionPlan>
	 */
	private static function createSubscriptionPlans( array $subscriptionPlansConfig, string $productId ): array {
		$plans = [];
		foreach ( $subscriptionPlansConfig as $subscriptionPlanValues ) {
			$interval = PaymentInterval::from( $subscriptionPlanValues['interval'] );
			$plans[$interval->name] = new SubscriptionPlan(
				$subscriptionPlanValues['name'],
				$productId,
				$interval,
				$subscriptionPlanValues['id']
			);
		}
		return $plans;
	}
}
