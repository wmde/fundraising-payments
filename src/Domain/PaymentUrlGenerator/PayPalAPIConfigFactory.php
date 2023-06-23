<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

class PayPalAPIConfigFactory {

	/**
	 * @phpstan-ignore-next-line
	 */
	public static function createConfig( array $allConfigs, string $productKey, string $languageKey ): PayPalAPIConfig {
		$subconfig = $allConfigs['paypal_api'];
		if ( !isset( $subconfig[$productKey] ) ) {
			throw new \LogicException( "'$productKey' does not exist in PayPal API configuration. Please check your configuration file." );
		}

		if ( !isset( $subconfig[$productKey][$languageKey] ) ) {
			throw new \LogicException( "'$languageKey' does not exist in PayPal API configuration for product '$productKey'. Please check your configuration file." );
		}

		$plans = self::createSubscriptionPlans(
			$subconfig[$productKey][$languageKey]['plans'],
			$subconfig[$productKey][$languageKey]['product_id']
		);
		return new PayPalAPIConfig(
			$subconfig[$productKey][$languageKey]['product_name'],
			$subconfig[$productKey]['return_url'],
			$subconfig[$productKey]['cancel_url'],
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
