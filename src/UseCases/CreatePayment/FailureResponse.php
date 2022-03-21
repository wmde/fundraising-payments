<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

class FailureResponse {
	public function __construct( readonly string $errorMessage ) {
	}

}
