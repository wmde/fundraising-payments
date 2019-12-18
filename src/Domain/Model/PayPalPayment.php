<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * @licence GNU GPL v2+
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class PayPalPayment implements PaymentMethod {

	private $payPalData;

	public function __construct( PayPalData $payPalData ) {
		$this->payPalData = $payPalData;
	}

	public function getId(): string {
		return PaymentMethod::PAYPAL;
	}

	public function getPayPalData(): PayPalData {
		return $this->payPalData;
	}

	public function addPayPalData( PayPalData $palPayData ): void {
		$this->payPalData = $palPayData;
	}

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): \DateTimeImmutable {
		return new \DateTimeImmutable( $this->payPalData->getPaymentTimestamp() );
	}

}