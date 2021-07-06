<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use RuntimeException;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\Request;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\Response;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;

class ExceptionThrowingSofortSofortClient implements SofortClient {

	private string $error;

	public function __construct( string $error ) {
		$this->error = $error;
	}

	public function get( Request $request ): Response {
		throw new RuntimeException( $this->error );
	}
}
