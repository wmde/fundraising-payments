<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

class SuccessResponse {
	public function __construct(
		public readonly int $paymentId
	) {
	}
}
