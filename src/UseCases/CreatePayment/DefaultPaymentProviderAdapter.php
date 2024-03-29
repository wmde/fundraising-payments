<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;

/**
 * This adapter does not contact external payment providers.
 *
 * It returns the input unchanged.
 */
class DefaultPaymentProviderAdapter implements PaymentProviderAdapter {
	public function fetchAndStoreAdditionalData( Payment $payment, DomainSpecificContext $domainSpecificContext ): Payment {
		return $payment;
	}

	public function modifyPaymentUrlGenerator(
		PaymentCompletionURLGenerator $paymentProviderURLGenerator,
		DomainSpecificContext $domainSpecificContext
	): PaymentCompletionURLGenerator {
		return $paymentProviderURLGenerator;
	}

}
