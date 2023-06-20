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
}
