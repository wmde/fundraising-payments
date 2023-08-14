<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

class PayPalOrder extends PayPalPaymentIdentifier {
	private string $transactionId;

	public function __construct( PayPalPayment $payment, string $transactionId ) {
		if ( $payment->getInterval()->isRecurring() ) {
			throw new \DomainException( self::class . ' can only be used for one-time payments' );
		}
		$trimmedTransactionId = trim( $transactionId );
		if ( empty( $trimmedTransactionId ) ) {
			throw new \DomainException( 'Transaction ID must not be empty' );
		}

		parent::__construct( $payment );
		$this->transactionId = $transactionId;
	}

	public function getTransactionId(): string {
		return $this->transactionId;
	}

}
