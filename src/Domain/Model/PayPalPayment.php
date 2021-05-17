<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class PayPalPayment implements PaymentMethod, BookablePayment {

	private PayPalData $payPalData;

	public function __construct( PayPalData $payPalData ) {
		$this->payPalData = $payPalData;
	}

	public function getId(): string {
		return PaymentMethod::PAYPAL;
	}

	public function getPayPalData(): PayPalData {
		return $this->payPalData;
	}

	/**
	 * @param PayPalData $palPayData
	 * @deprecated use bookPayment instead
	 */
	public function addPayPalData( PayPalData $palPayData ): void {
		$this->payPalData = $palPayData;
	}

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): DateTimeImmutable {
		return new DateTimeImmutable( $this->payPalData->getPaymentTimestamp() );
	}

	public function paymentCompleted(): bool {
		return $this->payPalData->getPayerId() !== '';
	}

	public function bookPayment( PaymentTransactionData $transactionData ): void {
		if ( !( $transactionData instanceof PayPalData ) ) {
			throw new InvalidArgumentException( sprintf( 'Illegal transaction data class for paypal: %s', get_class( $transactionData ) ) );
		}
		if ( $transactionData->getPayerId() === '' ) {
			throw new InvalidArgumentException( 'Transaction data must have payer ID' );
		}
		if ( $this->paymentCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->payPalData = $transactionData;
	}

}
