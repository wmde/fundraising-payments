<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;

class CreditCardPaymentSpy extends \WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment {
	private function __construct( int $id, Euro $amount, PaymentInterval $interval ) {
		parent::__construct( $id, $amount, $interval );
	}

	public function getAmount(): Euro {
		return $this->amount;
	}

	public function getInterval(): PaymentInterval {
		return $this->interval;
	}

	public function getValuationDate(): ?\DateTimeImmutable {
		return $this->valuationDate;
	}

	/**
	 * @return array<string,string>
	 */
	public function getBookingData(): array {
		return $this->bookingData;
	}

	public static function fromPayment( CreditCardPayment $sourcePayment ): self {
		$payment = new self( $sourcePayment->id, $sourcePayment->amount, $sourcePayment->interval );
		$payment->bookingData = $sourcePayment->bookingData;
		$payment->valuationDate = $sourcePayment->valuationDate;
		return $payment;
	}
}
