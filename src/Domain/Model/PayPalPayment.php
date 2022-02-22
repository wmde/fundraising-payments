<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class PayPalPayment implements BookablePayment {

	/**
	 * @var array<string,mixed>
	 */
	private array $bookingData;

	private ?DateTimeImmutable $valuationDate = null;

	public function __construct() {
		$this->bookingData = [];
	}

	public function hasExternalProvider(): bool {
		return true;
	}

	public function getValuationDate(): DateTimeImmutable {
		return $this->valuationDate;
	}

	public function paymentCompleted(): bool {
		return $this->bookingData->getPayerId() !== '';
	}

	/**
	 * @param array $transactionData
	 *
	 * @return void
	 *
	 * TODO: Turn paypal keys that exist in Fun App, PaypalNotificationController into useful enum
	 */
	public function bookPayment( array $transactionData ): void {
		if ( !empty( $transactionData['payer_id'] ) ) {
			throw new InvalidArgumentException( 'Transaction data must have payer ID' );
		}
		if ( $this->paymentCompleted() ) {
			throw new DomainException( 'Payment is already completed' );
		}
		$this->bookingData = $transactionData;
		$this->valuationDate = DateTimeImmutable::createFromMutable( $transactionData['payment_date'] );
	}

}
