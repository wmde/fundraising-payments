<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use WMDE\Euro\Euro;

class BankTransferPayment extends Payment implements CancellablePayment {

	private const PAYMENT_METHOD = 'UEB';

	/**
	 * This field is nullable to allow for anonymisation
	 *
	 * @var PaymentReferenceCode|null
	 */
	private ?PaymentReferenceCode $paymentReferenceCode;

	private bool $isCancelled = false;

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

	public function anonymise(): void {
		$this->paymentReferenceCode = null;
	}

	protected function getPaymentName(): string {
		return self::PAYMENT_METHOD;
	}

	protected function getPaymentSpecificLegacyData(): array {
		// "ueb_code" is a column name in the legacy "spenden" (donations) database table.
		// the donation repository code will have to put it there instead of the data blob
		$paymentReferenceCode = $this->getPaymentReferenceCode();
		return $paymentReferenceCode ? [ 'ueb_code' => $paymentReferenceCode ] : [];
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
		$paymentReferenceCode = $this->getPaymentReferenceCode();
		$subtypeValues = $paymentReferenceCode ? [ 'paymentReferenceCode' => $paymentReferenceCode ] : [];
		return array_merge(
			$parentValues,
			$subtypeValues
		);
	}

	/**
	 * @return string
	 */
	protected function getLegacyPaymentStatus(): string {
		if ( $this->isCancelled() ) { return LegacyPaymentStatus::CANCELLED->value;
		}
		return LegacyPaymentStatus::BANK_TRANSFER->value;
	}

	public function isCompleted(): bool {
		return true;
	}
}
