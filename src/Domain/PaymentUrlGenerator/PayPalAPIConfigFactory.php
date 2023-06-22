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
		$plans = [];
		foreach ( $subconfig[$productKey][$languageKey]['plans'] as $something ) {
			$intervalName = $something['interval'];
			$interval = PaymentInterval::from( $intervalName );
			$plans[$interval->name] = new SubscriptionPlan( $something['name'], $subconfig[$productKey][$languageKey]['product_id'], $interval, $something['id'] );
		}
		return new PayPalAPIConfig(
			$subconfig[$productKey][$languageKey]['product_name'],
			$subconfig[$productKey]['return_url'],
			$subconfig[$productKey]['cancel_url'],
			$plans
		);
	}
}
