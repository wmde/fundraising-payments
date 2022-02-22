<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;

class SofortPayment implements BookablePayment {

	private string $bankTransferCode;

	private ?string $transactionId = null;

	private ?DateTimeImmutable $valuationDate = null;

	public function __construct( string $bankTransferCode ) {
		$this->bankTransferCode = $bankTransferCode;
	}

	public function getBankTransferCode(): string {
		return $this->bankTransferCode;
	}

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return $this->valuationDate;
	}

	private function paymentCompleted(): bool {
		return $this->transactionId !== null;
	}

	public function bookPayment( array $transactionData ): void {
		if ( $this->paymentCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->transactionId = $transactionData['transactionId'];
		$this->valuationDate = new \DateTimeImmutable( $transactionData['valuationDate'] );
	}

}
