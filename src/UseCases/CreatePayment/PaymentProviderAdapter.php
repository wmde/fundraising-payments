<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;

/**
 * Implementations of this interface will allow contacting external Payment provider APIs to fetch data for various stages of the payment creation process
 */
interface PaymentProviderAdapter {
	public function fetchAndStoreAdditionalData( Payment $payment, DomainSpecificContext $domainSpecificContext ): Payment;

	public function modifyPaymentUrlGenerator( PaymentProviderURLGenerator $paymentProviderURLGenerator, DomainSpecificContext $domainSpecificContext ): PaymentProviderURLGenerator;
}