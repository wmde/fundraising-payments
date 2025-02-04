<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\KontoCheck;

class KontoCheckLibraryInitializationException extends \RuntimeException {

	public function __construct( ?int $code = null, ?\Exception $previous = null ) {
		$message = 'Could not initialize library with bank data file.';
		if ( $code !== null ) {
			$message .= ' Reason: ' . \kto_check_retval2txt( $code );
		}

		parent::__construct( $message, $code ?? 0, $previous );
	}
}
