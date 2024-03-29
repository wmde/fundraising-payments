<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class PayPalPaymentProviderAdapterConfigReader {

	public static function readConfig( string $fileName ): array {
		$config = Yaml::parseFile( $fileName );

		if ( !is_array( $config ) ) {
			throw new \DomainException( 'Configuration file must contain a nested array structure!' );
		}

		$processor = new Processor();
		$schema = new PayPalPaymentProviderAdapterConfigSchema();
		$processor->processConfiguration(
			$schema,
			[ $config ]
		);

		self::checkProductAndSubscriptionPlanIdsAreUnique( $config );

		return $config;
	}

	private static function checkProductAndSubscriptionPlanIdsAreUnique( array $config ): void {
		$allExistingProductIds = [];
		$allExistingSubscriptionPlanIds = [];
		foreach ( $config as $currentProduct ) {
			foreach ( $currentProduct as $currentConfig ) {
				$allExistingProductIds[] = $currentConfig['product_id'];
				foreach ( $currentConfig['subscription_plans'] as $currentPlanConfig ) {
					$allExistingSubscriptionPlanIds[] = $currentPlanConfig['id'];
				}
			}
		}
		$uniqueProductIds = array_unique( $allExistingProductIds );
		if ( count( $allExistingProductIds ) !== count( $uniqueProductIds ) ) {
			throw new \DomainException( "All product IDs in the configuration file must be unique!" );
		}

		$uniqueSubscriptionPlanIds = array_unique( $allExistingSubscriptionPlanIds );
		if ( count( $uniqueSubscriptionPlanIds ) !== count( $allExistingSubscriptionPlanIds ) ) {
			throw new \DomainException( "All subscription plan IDs in the configuration file must be unique!" );
		}
	}
}
