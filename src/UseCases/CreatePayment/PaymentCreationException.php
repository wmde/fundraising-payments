<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

class PaymentCreationException extends \Exception {
	public function __construct( string $message = "", ?\Throwable $previous = null ) {
		parent::__construct( $message, 0, $previous );
	}

}
