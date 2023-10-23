<?php

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateSubscriptionPlansForProduct;

class ErrorResult {

	public function __construct(
		public readonly string $message
	) {
	}
}
