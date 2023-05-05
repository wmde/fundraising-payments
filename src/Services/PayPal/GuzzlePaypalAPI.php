<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Log\LoggerInterface;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;

class GuzzlePaypalAPI implements PaypalAPI {

	private const ENDPOINT_LIST_PRODUCTS = '/v1/catalogs/products';

	/**
	 * @param Client $client client without auth configuration
	 * @param string $clientId PayPal API term for an auth username
	 * @param string $clientSecret PayPal API term for an auth password
	 */
	public function __construct(
		private readonly Client $client,
		private readonly string $clientId,
		private readonly string $clientSecret,
		private readonly LoggerInterface $logger
	) {
	}

	/**
	 * @param string $errorMessage
	 * @param array<string,string> $context
	 * @param \Exception|null $e
	 *
	 * @return PayPalAPIException
	 */
	private function createLoggedException( string $errorMessage, array $context, \Exception $e = null ): PayPalAPIException {
		$this->logger->error( $errorMessage, $context );
		throw new PayPalAPIException( $errorMessage, 0, $e );
	}

	public function listProducts(): array {
		$productResponse = $this->client->request(
			'POST',
			self::ENDPOINT_LIST_PRODUCTS,
			[ RequestOptions::HEADERS => [
				'Authorization' => "Basic {$this->clientId}:{$this->clientSecret}"
			] ]
		);

		$serverResponse = $productResponse->getBody()->getContents();
		try {
			$jsonProductResponse = json_decode( $serverResponse, true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			throw $this->createLoggedException(
				"Malformed JSON",
				[
				"serverResponse" => $serverResponse,
				"error" => $e->getMessage()
				],
				$e
			);
		}

		if ( !is_array( $jsonProductResponse ) || !isset( $jsonProductResponse['products'] ) ) {
			throw $this->createLoggedException( "Listing products failed!", [ "serverResponse" => $serverResponse ] );
		}

		if ( $jsonProductResponse['total_pages'] > 1 ) {
			throw $this->createLoggedException(
				"Paging is not supported because we don't have that many products!",
				[ "serverResponse" => $serverResponse ]
			);
		}

		$products = [];
		foreach ( $jsonProductResponse['products'] as $product ) {
			$products[] = new Product( $product['name'], $product['id'], $product['description'] );
		}
		return $products;
	}

}
