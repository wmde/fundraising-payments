<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * Identifiers can be used to look up Payments when an IPN (Instant Payment Notification) comes in.
 */
abstract class PayPalPaymentIdentifier {
	protected PayPalPayment $payment;

	/**
	 * @param PayPalPayment $payment
	 */
	public function __construct( PayPalPayment $payment ) {
		$this->payment = $payment;
	}

	public function getPayment(): PayPalPayment {
		return $this->payment;
	}
}
