<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DateTimeInterface;
use DomainException;
use WMDE\Euro\Euro;

class SofortPayment extends Payment implements BookablePayment {

	private const PAYMENT_METHOD = 'SUB';

	/**
	 * This field is nullable to allow for anonymisation
	 *
	 * @var PaymentReferenceCode|null
	 */
	private ?PaymentReferenceCode $paymentReferenceCode;

	private ?string $transactionId = null;

	private ?DateTimeImmutable $valuationDate = null;

	private function __construct( int $id, Euro $amount, PaymentInterval $interval, ?PaymentReferenceCode $paymentReference ) {
		if ( $interval !== PaymentInterval::OneTime ) {
			throw new \InvalidArgumentException( "Provided payment interval must be 0 (= one time payment) for Sofort payments." );
		}
		parent::__construct( $id, $amount, $interval, self::PAYMENT_METHOD );
		$this->paymentReferenceCode = $paymentReference;
	}

	public static function create( int $id, Euro $amount, PaymentInterval $interval, PaymentReferenceCode $paymentReferenceCode ): self {
		return new self( $id, $amount, $interval, $paymentReferenceCode );
	}

	public function getPaymentReferenceCode(): string {
		if ( $this->paymentReferenceCode === null ) {
			return '';
		}
		return $this->paymentReferenceCode->getFormattedCode();
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @return bool
	 */
	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return $this->valuationDate;
	}

	public function isCompleted(): bool {
		return $this->transactionId !== null;
	}

	/**
	 * @param array<string,mixed> $transactionData
	 *
	 * @return void
	 * @throws \DomainException
	 */
	public function bookPayment( array $transactionData ): void {
		if ( $this->isCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		if ( empty( $transactionData['transactionId'] ) ) {
			throw new DomainException( 'Transaction ID missing' );
		}
		$this->transactionId = strval( $transactionData['transactionId'] );
		$valuationDate = DateTimeImmutable::createFromFormat( DateTimeInterface::ATOM, strval( $transactionData['valuationDate'] ) );
		if ( $valuationDate === false ) {
			$msg = 'Error in valuation date.';
			$errors = DateTimeImmutable::getLastErrors();
			if ( is_array( $errors ) ) {
				$msg .= ' ' . var_export( $errors, true );
			}
			throw new DomainException( $msg );
		}
		$this->valuationDate = $valuationDate;
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @return array<string,string>
	 */
	public function getLegacyData(): array {
		return [];
	}

	public function anonymise(): void {
		$this->paymentReferenceCode = null;
	}
}
