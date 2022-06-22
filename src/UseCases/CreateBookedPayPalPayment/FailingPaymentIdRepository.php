<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment;

use WMDE\Fundraising\PaymentContext\Domain\PaymentIdRepository;

class FailingPaymentIdRepository implements PaymentIdRepository {

	public function __construct( private string $message ) {
	}

	public function getNewId(): int {
		throw new \LogicException( $this->message );
	}

}
