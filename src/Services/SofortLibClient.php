<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services;

use RuntimeException;
use Sofort\SofortLib\Sofortueberweisung;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort\Request;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort\Response;
use WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator\Sofort\SofortClient;

/**
 * Facade in front of Sofortueberweisung, an API to generate URLs of Sofort's checkout process
 */
class SofortLibClient implements SofortClient {

	private Sofortueberweisung $api;

	public function __construct( string $configkey ) {
		$this->api = new Sofortueberweisung( $configkey );
	}

	/**
	 * Set API to use instead of the one chosen by the facade
	 * @param Sofortueberweisung $sofortueberweisung
	 */
	public function setApi( Sofortueberweisung $sofortueberweisung ): void {
		$this->api = $sofortueberweisung;
	}

	/**
	 * Perform the given request and return a response
	 *
	 * @param Request $request
	 * @return Response
	 * @throws RuntimeException
	 */
	public function get( Request $request ): Response {
		// Mapping currency amount to 3rd party float format. Known flaw
		$this->api->setAmount( $request->getAmount()->getEuroFloat() );

		$this->api->setCurrencyCode( $request->getCurrencyCode() );

		$reasons = $request->getReasons();
		$this->api->setReason( $reasons[0] ?? '', $reasons[1] ?? '' );

		$this->api->setSuccessUrl( $request->getSuccessUrl(), true );
		$this->api->setAbortUrl( $request->getAbortUrl() );
		$this->api->setNotificationUrl( $request->getNotificationUrl() );
		$this->api->setLanguageCode( $request->getLocale() );

		$this->api->sendRequest();

		if ( $this->api->isError() ) {
			throw new RuntimeException( $this->api->getError() );
		}

		$response = new Response();
		$response->setPaymentUrl( $this->api->getPaymentUrl() );
		$response->setTransactionId( $this->api->getTransactionId() );

		return $response;
	}
}
