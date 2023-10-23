<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

class SuccessResponse {
	/**
	 * @param int $paymentId
	 * @param string $paymentCompletionUrl The URL to which the user should be redirected to complete the payment.
	 *      Can be the payment provider or the URL of the application confirmation page. Should never be empty.
	 *      Generated in the use case by a {@see PaymentCompletionURLGenerator} implementation.
	 * @param bool $paymentComplete
	 */
	public function __construct(
		public readonly int $paymentId,
		public readonly string $paymentCompletionUrl,
		public readonly bool $paymentComplete
	) {
	}
}
