<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BankTransferPayment implements PaymentMethod {

	private string $bankTransferCode;

	public function __construct( string $bankTransferCode ) {
		$this->bankTransferCode = $bankTransferCode;
	}

	public function getId(): string {
		return PaymentMethod::BANK_TRANSFER;
	}

	public function getBankTransferCode(): string {
		return $this->bankTransferCode;
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
