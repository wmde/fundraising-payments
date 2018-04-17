<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class BankTransferPayment extends BasePaymentMethod {

	private $bankTransferCode;

	public function __construct( string $bankTransferCode ) {
		$this->bankTransferCode = $bankTransferCode;
	}

	public function getId(): string {
		return PaymentMethod::BANK_TRANSFER;
	}

	public function getBankTransferCode(): string {
		return $this->bankTransferCode;
	}

}