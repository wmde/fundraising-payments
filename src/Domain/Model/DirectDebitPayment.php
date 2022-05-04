<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use WMDE\Euro\Euro;

class DirectDebitPayment extends Payment implements CancellablePayment {

	private const PAYMENT_METHOD = 'BEZ';

	/**
	 * @var Iban|null
	 * This field is nullable to allow for anonymisation
	 */
	private ?Iban $iban;

	/**
	 * @var string|null
	 * This field is nullable to allow for anonymisation
	 */
	private ?string $bic;

	private bool $isCancelled = false;

	private function __construct( int $id, Euro $amount, PaymentInterval $interval, Iban $iban = null, string $bic = null ) {
		parent::__construct( $id, $amount, $interval, self::PAYMENT_METHOD );
		$this->iban = $iban;
		$this->bic = $bic;
	}

	public static function create( int $id, Euro $amount, PaymentInterval $interval, Iban $iban, string $bic ): self {
		return new self( $id, $amount, $interval, $iban, $bic );
	}

	public function anonymise(): void {
		$this->iban = null;
		$this->bic = null;
	}

	protected function getPaymentName(): string {
		return self::PAYMENT_METHOD;
	}

	protected function getPaymentSpecificLegacyData(): array {
		return [
			'iban' => $this->iban ? $this->iban->toString() : '',
			'bic' => $this->bic ?? ''
		];
	}

	public function getIban(): ?Iban {
		return $this->iban;
	}

	public function getBic(): ?string {
		return $this->bic;
	}

	public function isCancelled(): bool {
		return $this->isCancelled;
	}

	public function cancel(): void {
		$this->isCancelled = true;
	}

	public function isCancellable(): bool {
		return !$this->isCancelled();
	}

	public function getDisplayValues(): array {
		$parentValues = parent::getDisplayValues();
		$subtypeValues = $this->getPaymentSpecificLegacyData();
		return array_merge(
			$parentValues,
			$subtypeValues
		);
	}

	/**
	 * @return string (N is the NEW payment status (legacy) for directdebit payments)
	 */
	protected function getLegacyPaymentStatus(): string {
		if ( $this->isCancelled() ) { return LegacyPaymentStatus::CANCELLED->value;
		}
		return LegacyPaymentStatus::DIRECT_DEBIT->value;
	}

	public function isCompleted(): bool {
		return true;
	}
}
