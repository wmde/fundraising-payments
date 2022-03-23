<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

interface TranslatableDescription {

	/**
	 * @param AdditionalPaymentData $textParameters contains values like amount, interval ...
	 *
	 * @return string
	 */
	public function getText( AdditionalPaymentData $textParameters ): string;

}
