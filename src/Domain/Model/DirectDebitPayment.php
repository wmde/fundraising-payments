<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DirectDebitPayment implements PaymentMethod {

	private BankData $bankData;

	public function __construct( BankData $bankData ) {
		$this->bankData = $bankData;
	}

	public function getId(): string {
		return PaymentMethod::DIRECT_DEBIT;
	}

	public function getBankData(): BankData {
		return $this->bankData;
	}

	public function hasExternalProvider(): bool {
		return false;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return null;
	}

	public function paymentCompleted(): bool {
		return true;
	}
}
