<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

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

}
