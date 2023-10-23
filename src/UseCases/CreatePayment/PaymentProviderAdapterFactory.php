<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

interface PaymentProviderAdapterFactory {
	public function createProvider( Payment $payment, URLAuthenticator $authenticator ): PaymentProviderAdapter;
}
