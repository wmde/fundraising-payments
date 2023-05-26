<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

class SubscriptionPlan {
	public function __construct(
		public readonly string $name,
		public readonly string $productId,
		public readonly int $monthlyInterval,
		public readonly ?string $description = null
	) {
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
