<?php
declare(strict_types=1);

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

use DateTimeImmutable;

class Subscription
{
	public function __construct(
		public readonly string $id,
		public readonly DateTimeImmutable $subscriptionStart,
		public readonly string $confirmationLink
	) {}
}
