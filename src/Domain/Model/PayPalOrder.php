<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * Creating an order via API only returns the order ID.
 * The transaction ID must be set later when the user returns from the payment provider
 */
class PayPalOrder extends PayPalPaymentIdentifier {
	private ?string $transactionId;
	private string $orderId;

	public function __construct( PayPalPayment $payment, string $orderID, ?string $transactionId = null ) {
		if ( $payment->getInterval()->isRecurring() ) {
			throw new \DomainException( self::class . ' can only be used for one-time payments' );
		}
		$trimmedOrderId = trim( $orderID );
		if ( empty( $trimmedOrderId ) ) {
			throw new \DomainException( 'Order ID must not be empty' );
		}

		parent::__construct( $payment );
		$this->orderId = $trimmedOrderId;
		$this->transactionId = $transactionId;
	}

	public function getTransactionId(): ?string {
		return $this->transactionId;
	}

	public function setTransactionId( string $transactionId ): void {
		if ( trim( $transactionId ) === '' ) {
			throw new \DomainException( 'Transaction ID must not be empty when setting it explicitly' );
		}
		if ( $this->transactionId !== null && $this->transactionId !== $transactionId ) {
			throw new \DomainException( 'Transaction ID must not be changed' );
		}
		$this->transactionId = $transactionId;
	}

	public function getOrderId(): string {
		return $this->orderId;
	}

}
