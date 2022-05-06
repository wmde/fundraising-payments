<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreateBookedPayPalPayment;

use WMDE\Fundraising\PaymentContext\Domain\Repositories\PaymentIDRepository;

class FailingPaymentIdRepository implements PaymentIDRepository {

	public function __construct( private string $message ) {
	}

	public function getNewID(): int {
		throw new \LogicException( $this->message );
	}

}
