<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

class PayPalSubscription extends PayPalPaymentIdentifier {
	private string $subscriptionId;

	public function __construct( PayPalPayment $payment, string $subscriptionId ) {
		if ( !$payment->getInterval()->isRecurring() ) {
			throw new \DomainException( self::class . ' can only be used for recurring payments' );
		}
		$trimmedSubscriptionId = trim( $subscriptionId );
		if ( empty( $trimmedSubscriptionId ) ) {
			throw new \DomainException( 'Subscription ID must not be empty' );
		}
		parent::__construct( $payment );
		$this->subscriptionId = $subscriptionId;
	}

	public function getSubscriptionId(): string {
		return $this->subscriptionId;
	}

}
