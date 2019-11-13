<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;

/**
 * @licence GNU GPL v2+
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class CreditCardPayment implements PaymentMethod {

	private $creditCardData;

	public function __construct( CreditCardTransactionData $creditCardData = null ) {
		$this->creditCardData = $creditCardData;
	}

	public function getId(): string {
		return PaymentMethod::CREDIT_CARD;
	}

	public function getCreditCardData(): ?CreditCardTransactionData {
		return $this->creditCardData;
	}

	public function addCreditCardTransactionData( CreditCardTransactionData $creditCardData ): void {
		$this->creditCardData = $creditCardData;
	}

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): ?DateTimeImmutable {
		return DateTimeImmutable::createFromMutable( $this->creditCardData->getTransactionTimestamp() );
	}

}