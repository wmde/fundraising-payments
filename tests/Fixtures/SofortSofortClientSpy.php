<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort\Request;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort\Response;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort\SofortClient;

class SofortSofortClientSpy implements SofortClient {

	private Response $response;

	/**
	 * @var Request
	 */
	public Request $request;

	public function __construct( string $responseUrl ) {
		$response = new Response();
		$response->setPaymentUrl( $responseUrl );
		$this->response = $response;
		$this->request = new Request();
	}

	public function get( Request $request ): Response {
		$this->request = $request;
		return $this->response;
	}
}
