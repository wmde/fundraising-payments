<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

use UnexpectedValueException;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;

class SubscriptionPlan {
	public function __construct(
		public readonly string $name,
		public readonly string $productId,
		public readonly int $monthlyInterval,
		public readonly ?string $id = null,
		public readonly ?string $description = null
	) {
	}

	/**
	 * @param array<string,mixed> $apiData A single plan item form the PayPal API request
	 * @return SubscriptionPlan
	 */
	public static function createFromJSON( array $apiData ): SubscriptionPlan {
		// Theoretically, we'd want to check name, product_id, and id in $apiData,
		// but the billing_cycles check should be sufficient to detect broken data from the PayPal API

		if ( empty( $apiData['billing_cycles'] ) || !is_array( $apiData['billing_cycles'] ) || count( $apiData['billing_cycles'] ) !== 1 ) {
			throw new PayPalAPIException( 'Wrong billing cycle data' );
		}
		$billingCycle = $apiData['billing_cycles'][0];

		if ( !isset( $billingCycle['frequency'] ) || !isset( $billingCycle['frequency']['interval_count'] ) ) {
			throw new PayPalAPIException( 'Wrong frequency data in billing cycle' );
		}
		$frequency = $billingCycle['frequency'];

		if ( ( $frequency['interval_unit'] ?? '' ) !== 'MONTH' ) {
			throw new PayPalAPIException( 'interval_unit must be MONTH' );
		}
		$monthlyInterval = $frequency['interval_count'];
		$description = $apiData['description'] ?? '';

		// Make static typechecker happy, using strval on mixed throws errors
		if (
			!is_scalar( $apiData['name'] ) ||
			!is_scalar( $apiData['product_id'] ) ||
			!is_scalar( $apiData['id'] ) ||
			!is_scalar( $description )
		) {
			throw new UnexpectedValueException( 'Scalar value expected' );
		}

		return new SubscriptionPlan(
			strval( $apiData['name'] ),
			strval( $apiData['product_id'] ),
			intval( $monthlyInterval ),
			strval( $apiData['id'] ),
			strval( $description ),
		);
	}

	public function toJSON(): string {
		return json_encode( [
			"name" => $this->name,
			"product_id" => $this->productId,
			"description" => $this->description,
			"billing_cycles" => [ [
				"sequence" => 0,
				"tenure_type" => "REGULAR",
				"frequency" => [
					"interval_unit" => "MONTH",
					"interval_count" => $this->monthlyInterval
				],
				"total_cycles" => 0
			] ],
			"payment_preferences" => [
				"auto_bill_outstanding" => true,
				"setup_fee_failure_action" => "CONTINUE",
				// TODO ask PM if we should have a threshold
				"payment_failure_threshold" => 0,
				"setup_fee" => [
					"currency_code" => "EUR",
					"value" => "0"
				]
			]
		], JSON_THROW_ON_ERROR );
	}

}
