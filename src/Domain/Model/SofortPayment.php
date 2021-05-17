<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTime;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

class SofortPayment implements PaymentMethod, BookablePayment {

	private string $bankTransferCode = '';

	/**
	 * This is mutable by accident and should be immutable, see https://phabricator.wikimedia.org/T281895
	 * @var null|DateTime
	 */
	private ?DateTime $confirmedAt;

	public function __construct( string $bankTransferCode ) {
		$this->bankTransferCode = $bankTransferCode;
		$this->confirmedAt = null;
	}

	public function getId(): string {
		return PaymentMethod::SOFORT;
	}

	public function getBankTransferCode(): string {
		return $this->bankTransferCode;
	}

	public function getConfirmedAt(): ?DateTime {
		return $this->confirmedAt;
	}

	/**
	 * @param DateTime|null $confirmedAt
	 * @deprecated Use bookPayment() instead
	 */
	public function setConfirmedAt( ?DateTime $confirmedAt ): void {
		$this->confirmedAt = $confirmedAt;
	}

	/**
	 * @deprecated Use paymentCompleted() instead
	 */
	public function isConfirmedPayment(): bool {
		return $this->getConfirmedAt() !== null;
	}

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return $this->confirmedAt ? DateTimeImmutable::createFromMutable( $this->confirmedAt ) : null;
	}

	public function paymentCompleted(): bool {
		return $this->getConfirmedAt() !== null;
	}

	public function bookPayment( PaymentTransactionData $transactionData ): void {
		if ( !( $transactionData instanceof SofortTransactionData ) ) {
			throw new InvalidArgumentException( sprintf( 'Illegal transaction data class for Sofort payment: %s', get_class( $transactionData ) ) );
		}
		if ( $this->paymentCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->confirmedAt = DateTime::createFromImmutable( $transactionData->getValuationDate() );
	}

}
