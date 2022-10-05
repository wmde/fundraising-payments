<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CancelPayment;

class SuccessResponse {
	public function __construct(
		public readonly bool $paymentIsCompleted
	) {
	}
}
