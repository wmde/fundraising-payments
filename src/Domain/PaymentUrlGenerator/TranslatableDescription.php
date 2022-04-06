<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

interface TranslatableDescription {

	/**
	 * @param Euro $paymentAmount
	 * @param PaymentInterval $paymentInterval
	 *
	 * @return string
	 */
	public function getText( Euro $paymentAmount, PaymentInterval $paymentInterval ): string;

}
