<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;

interface UrlGeneratorFactory {
	public function createURLGenerator( Payment $payment, URLAuthenticator $authenticator ): PaymentCompletionURLGenerator;
}
