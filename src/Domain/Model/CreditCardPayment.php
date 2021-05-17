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
class CreditCardPayment implements PaymentMethod, BookablePayment {

	private ?CreditCardTransactionData $creditCardData;

	public function __construct( CreditCardTransactionData $creditCardData = null ) {
		$this->creditCardData = $creditCardData;
	}

	public function getId(): string {
		return PaymentMethod::CREDIT_CARD;
	}

	public function getCreditCardData(): ?CreditCardTransactionData {
		return $this->creditCardData;
	}

	/**
	 * @param CreditCardTransactionData $creditCardData
	 * @deprecated use bookPayment instead
	 */
	public function addCreditCardTransactionData( CreditCardTransactionData $creditCardData ): void {
		$this->creditCardData = $creditCardData;
	}

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return DateTimeImmutable::createFromMutable( $this->creditCardData->getTransactionTimestamp() );
	}

	public function paymentCompleted(): bool {
		return $this->creditCardData !== null && $this->creditCardData->getTransactionId() !== '';
	}

	public function bookPayment( PaymentTransactionData $transactionData ): void {
		if ( !( $transactionData instanceof CreditCardTransactionData ) ) {
			throw new InvalidArgumentException( sprintf( 'Illegal transaction data class for credit card: %s', get_class( $transactionData ) ) );
		}
		if ( $transactionData->getTransactionId() === '' ) {
			throw new InvalidArgumentException( 'Credit card transaction data must have transaction id' );
		}
		if ( $this->paymentCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->creditCardData = $transactionData;
	}

}
