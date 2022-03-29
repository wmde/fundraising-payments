<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use WMDE\Euro\Euro;

class BankTransferPayment extends Payment {

	private const PAYMENT_METHOD = 'UEB';

	/**
	 * This field is nullable to allow for anonymisation
	 *
	 * @var PaymentReferenceCode|null
	 */
	private ?PaymentReferenceCode $paymentReferenceCode;

	private function __construct( int $id, Euro $amount, PaymentInterval $interval, ?PaymentReferenceCode $paymentReference ) {
		parent::__construct( $id, $amount, $interval, self::PAYMENT_METHOD );

		$this->paymentReferenceCode = $paymentReference;
	}

	public static function create( int $id, Euro $amount, PaymentInterval $interval, PaymentReferenceCode $paymentReference ): self {
		return new self( $id, $amount, $interval, $paymentReference );
	}

	public function getPaymentReferenceCode(): string {
		if ( $this->paymentReferenceCode === null ) {
			return '';
		}
		return $this->paymentReferenceCode->getFormattedCode();
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

	public function anonymise(): void {
		$this->paymentReferenceCode = null;
	}
}
