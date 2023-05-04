<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use JsonException;

class GuzzlePaypalAPI implements PaypalAPI {

	const ENDPOINT_AUTH = '/v1/oauth2/token';
	const ENDPOINT_LIST_PRODUCTS = '/v1/catalogs/products';

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
			self::ENDPOINT_AUTH,
			[
				RequestOptions::AUTH => [ $this->clientId, $this->clientSecret ],
				RequestOptions::HEADERS => [ 'Content-Type' => "application/x-www-form-urlencoded" ],
				RequestOptions::FORM_PARAMS => [ 'grant_type' => 'client_credentials' ]
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

		$this->client->request(
			'POST',
			self::ENDPOINT_LIST_PRODUCTS,
			[ RequestOptions::HEADERS => [ 'Authorization' => "Bearer $accessToken" ] ]
		);
		return [];
	}

}
