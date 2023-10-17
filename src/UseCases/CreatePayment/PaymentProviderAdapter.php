<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;

/**
 * Implementations of this interface will allow contacting external Payment provider APIs to fetch data for various stages of the payment creation process
 */
interface PaymentProviderAdapter {
	public function fetchAndStoreAdditionalData( Payment $payment, DomainSpecificContext $domainSpecificContext ): Payment;

	public function modifyPaymentUrlGenerator( PaymentCompletionURLGenerator $paymentProviderURLGenerator, DomainSpecificContext $domainSpecificContext ): PaymentCompletionURLGenerator;
}
