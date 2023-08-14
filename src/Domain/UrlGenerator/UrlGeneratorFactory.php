<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\UrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;

interface UrlGeneratorFactory {
	public function createURLGenerator( Payment $payment ): PaymentProviderURLGenerator;
}
