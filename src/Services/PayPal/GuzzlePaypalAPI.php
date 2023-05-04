<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use GuzzleHttp\Client;
use JsonException;

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
		$authResponse = $this->client->request(
			'POST',
			'/v1/oauth2/token',
			[
				'auth' => [ $this->clientId, $this->clientSecret ],
				'headers' => [ 'Content-Type' => "application/x-www-form-urlencoded" ],
				'form_params' => [ 'grant_type' => 'client_credentials' ]
			],
		);

		try {
			$jsonAuthResponse = json_decode( $authResponse->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			throw new PayPalAPIException( "Malformed JSON", 0, $e );
		}

		if ( !is_array( $jsonAuthResponse ) || !isset( $jsonAuthResponse['access_token'] ) ) {
			throw new PayPalAPIException( "Authentication failed!" );
		}
		$accessToken = $jsonAuthResponse[ 'access_token' ];

		$this->client->request( 'POST', '', [ 'headers' => [ 'Authorization' => "Bearer $accessToken" ] ] );
		return [];
	}

}
