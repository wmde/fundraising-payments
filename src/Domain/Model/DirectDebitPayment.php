<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use WMDE\Euro\Euro;

class DirectDebitPayment extends Payment {

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

	public function hasExternalProvider(): bool {
		return false;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return null;
	}

	public function isCompleted(): bool {
		return true;
	}

	public function getLegacyData(): array {
		return [];
	}

	public function getIban(): ?Iban {
		return $this->iban;
	}

	public function getBic(): ?string {
		return $this->bic;
	}
}
