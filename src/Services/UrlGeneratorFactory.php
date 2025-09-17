<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;

/**
 * This interface allows for payment-specific creation of a {@see PaymentCompletionURLGenerator}.
 *
 * The PaymentCompletionURLGenerator generates a URL to redirect to after the "action that the payment is for"
 * (e.g. donation, membership) is finished, i.e. at the end of an HTTP controller.
 * It might be a URL for an external payment provider or a success page of the application.
 *
 * The URL is part of a success response. The application may use the URL or ignore it.
 */
interface UrlGeneratorFactory {

	/**
	 * @param Payment $payment
	 * @param URLAuthenticator $authenticator The authenticator may add additional authentication parameters to the URL,
	 * 			that prevent enumeration attacks. It is application- and payment-specific, depending on what kind
	 * 			of URL the generator creates.
	 *
	 * @return PaymentCompletionURLGenerator
	 */
	public function createURLGenerator( Payment $payment, URLAuthenticator $authenticator ): PaymentCompletionURLGenerator;
}
