<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

use DateTimeImmutable;
use WMDE\Euro\Euro;

class SubscriptionParameters {
	public function __construct(
		public readonly SubscriptionPlan $subscriptionPlan,
		public readonly DateTimeImmutable $startTime,
		public readonly Euro $amount,
		public readonly string $returnUrl,
		public readonly string $cancelUrl
	) {
	}

	public function toJSON(): string {
		return json_encode(
			[
				"plan_id" => $this->subscriptionPlan->id,
				"start_time" => $this->startTime
					->setTimezone( new \DateTimeZone( 'UTC' ) )
					->format( 'Y-m-d\TH:i:s\Z' ),
				"quantity" => "1",
				"plan" => [
					"billing_cycles" => SubscriptionPlan::getBillingCycle(
						$this->subscriptionPlan->monthlyInterval->value,
						$this->amount->getEuroString()
					)
				],
				"application_context" => [
					"brand_name" => "wikimedia germany",
					"return_url" => $this->returnUrl,
					"cancel_url" => $this->cancelUrl
				]
			],
			JSON_THROW_ON_ERROR
		);
	}
}
