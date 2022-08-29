<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\PaymentRepository;

class PaymentRepositorySpy implements PaymentRepository {

	/**
	 * @param array<int,Payment> $payments
	 */
	public function __construct(
		public array $payments
	) {
	}

	public function storePayment( Payment $payment ): void {
		$this->payments[ $payment->getId() ] = $payment;
	}

	public function getPaymentById( int $id ): Payment {
		return $this->payments[ $id ];
	}
}
