<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CancelPayment;

class FailureResponse {
	public function __construct(
		public readonly string $message
	) {
	}
}
