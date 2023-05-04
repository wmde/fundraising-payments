<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use JsonException;

class GuzzlePaypalAPI implements PaypalAPI {

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
		$productResponse = $this->client->request(
			'POST',
			self::ENDPOINT_LIST_PRODUCTS,
			[ RequestOptions::HEADERS => [
				'Authorization' => "Basic {$this->clientId}:{$this->clientSecret}"
			] ]
		);
		try {
			$jsonProductResponse = json_decode( $productResponse->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			throw new PayPalAPIException( "Malformed JSON", 0, $e );
		}

		if ( !is_array( $jsonProductResponse ) || !isset( $jsonProductResponse['products'] ) ) {
			throw new PayPalAPIException( "Listing products failed!" );
		}
		return [];
	}

}
