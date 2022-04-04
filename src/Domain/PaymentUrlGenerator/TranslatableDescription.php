<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

interface TranslatableDescription {

	/**
	 * @param Payment $payment contains values like amount, interval ...
	 *
	 * @return string
	 */
	public function getText( Payment $payment ): string;

}
