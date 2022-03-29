<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\BookPayment;

class FollowUpSuccessResponse extends SuccessResponse {

	public function __construct(
		public readonly int $parentPaymentId,
		public readonly int $childPaymentId
	) {
	}
}
