<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class AdditionalPaymentData {

	public function __construct(
		public readonly string $paymentReferenceCode,
		public readonly Euro $amount,
		public readonly PaymentInterval $interval,
	) {
	}
}
