<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use GuzzleHttp\Client;

class GuzzlePaypalAPI implements PaypalAPI {

	/**
	 * @param Client $client client without auth configuration
	 * @param string $clientId PayPal API term for an auth username
	 * @param string $clientSecret PayPal API term for an auth password
	 */
	public function __construct(
		private readonly Client $client,
		private readonly string $clientId,
		private readonly string $clientSecret
	) {
	}

	public function listProducts(): array {
		$authResponse = $this->client->request( 'POST', '/v1/oauth2/token', [ 'auth' => [ $this->clientId, $this->clientSecret ] ] );
		// TODO find out what PayPal sends as a response when auth is invalid and throw auth exception
		// TODO guard against JSON_DECODE errors and throw auth exception
		$jsonAuthResponse = json_decode( $authResponse->getBody()->getContents(), true );
		// TODO check for missing access_token and throw auth exception
		$accessToken = $jsonAuthResponse[ 'access_token' ];

		$this->client->request( 'POST', '', [ 'headers' => [ 'Authorization' => "Bearer $accessToken" ] ] );
		return [];
	}

}
