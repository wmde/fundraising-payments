<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment;

class FailureResponse {
	public function __construct(
		public readonly string $message
	) {
	}
}
