<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use WMDE\Euro\Euro;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BankTransferPayment extends Payment {

	private const PAYMENT_METHOD = 'UEB';

	private string $bankTransferCode;

	public function __construct( int $id, Euro $amount, PaymentInterval $interval, string $bankTransferCode ) {
		parent::__construct( $id, $amount, $interval, self::PAYMENT_METHOD );

		if ( $bankTransferCode === '' ) {
			throw new \InvalidArgumentException( 'Bank Transfer Code must not be empty' );
		}

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

	public function getLegacyData(): array {
		return [];
	}
}
