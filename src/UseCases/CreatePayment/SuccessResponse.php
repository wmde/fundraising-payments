<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\PaymentProviderURLGenerator;

class SuccessResponse {
	public function __construct(
		public readonly int $paymentId,
		public readonly PaymentProviderURLGenerator $paymentProviderURLGenerator,
		public readonly bool $paymentComplete
	) {
	}
}
