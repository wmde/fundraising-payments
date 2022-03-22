<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use WMDE\Euro\Euro;

class SofortPayment extends Payment implements BookablePayment {

	private const PAYMENT_METHOD = 'SUB';

	private string $paymentReferenceCode;

	private ?string $transactionId = null;

	private ?DateTimeImmutable $valuationDate = null;

	public function __construct( int $id, Euro $amount, PaymentInterval $interval, string $paymentReferenceCode ) {
		if ( $interval !== PaymentInterval::OneTime ) {
			throw new \InvalidArgumentException( "Provided payment interval must be 0 (= one time payment) for Sofort payments." );
		}
		parent::__construct( $id, $amount, $interval, self::PAYMENT_METHOD );
		$this->paymentReferenceCode = $paymentReferenceCode;
	}

	public function getPaymentReferenceCode(): string {
		return $this->paymentReferenceCode;
	}

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return $this->valuationDate;
	}

	public function paymentCompleted(): bool {
		return $this->transactionId !== null;
	}

	/**
	 * @param array<string,mixed> $transactionData
	 *
	 * @return void
	 * @throws \DomainException|\Exception
	 */
	public function bookPayment( array $transactionData ): void {
		if ( $this->paymentCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->transactionId = $transactionData['transactionId'];
		$this->valuationDate = new \DateTimeImmutable( $transactionData['valuationDate'] );
	}

	public function getLegacyData(): array {
		return [];
	}
}
