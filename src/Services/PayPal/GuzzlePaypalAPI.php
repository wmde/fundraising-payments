<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Log\LoggerInterface;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

class GuzzlePaypalAPI implements PaypalAPI {

	private const ENDPOINT_PRODUCTS = '/v1/catalogs/products';
	private const ENDPOINT_SUBSCRIPTION_PLANS = '/v1/billing/plans';

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
			'GET',
			self::ENDPOINT_PRODUCTS,
			[ RequestOptions::HEADERS => [
				// TODO this is wrong, we must base64 encode it, see https://stackoverflow.com/a/62002538/130121
				'Authorization' => "Basic {$this->clientId}:{$this->clientSecret}"
			] ]
		);

		$serverResponse = $productResponse->getBody()->getContents();
		$jsonProductResponse = $this->safelyDecodeJSON( $serverResponse );

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
			$products[] = new Product( $product['name'], $product['id'], $product['description'] ?? '' );
		}
		return $products;
	}

	public function createProduct( Product $product ): Product {
		$response = $this->client->request(
			'POST',
			self::ENDPOINT_PRODUCTS,
			[
				RequestOptions::HEADERS => [
					'Authorization' => "Basic {$this->clientId}:{$this->clientSecret}",
					'Content-Type' => "application/json",
					'Accept' => "application/json",
					'Prefer' => 'return=representation'
				],
				RequestOptions::BODY => $product->toJSON()
			]
		);

		$serverResponse = $response->getBody()->getContents();
		$jsonProductResponse = $this->safelyDecodeJSON( $serverResponse );

		if ( !is_array( $jsonProductResponse ) || empty( $jsonProductResponse['name'] ) || empty( $jsonProductResponse['id'] ) ) {
			throw $this->createLoggedException(
				'Server did not send product data back',
				[ "serverResponse" => $serverResponse ]
			);
		}
		return new Product(
			$jsonProductResponse['name'],
			$jsonProductResponse['id'],
			$jsonProductResponse['description'] ?? null
		);
	}

	public function listSubscriptionPlansForProduct( string $productId ): array {
		$planResponse = $this->client->request(
			'GET',
			self::ENDPOINT_SUBSCRIPTION_PLANS,
			[
				RequestOptions::HEADERS => [
					'Authorization' => "Basic {$this->clientId}:{$this->clientSecret}",
					'Accept' => "application/json",
					'Prefer' => 'return=representation'
				],
				RequestOptions::QUERY => [ 'product_id' => $productId ]
			]
		);

		$serverResponse = $planResponse->getBody()->getContents();
		$jsonPlanResponse = $this->safelyDecodeJSON( $serverResponse );

		if ( !is_array( $jsonPlanResponse ) || !isset( $jsonPlanResponse['plans'] ) ) {
			throw $this->createLoggedException( "Listing subscription plans failed!", [ "serverResponse" => $serverResponse ] );
		}

		if ( $jsonPlanResponse['total_pages'] > 1 ) {
			throw $this->createLoggedException(
				"Paging is not supported because each product should not have more than 4 payment intervals!",
				[ "serverResponse" => $serverResponse ]
			);
		}

		$plans = [];
		foreach ( $jsonPlanResponse['plans'] as $plan ) {
			$plans[] = SubscriptionPlan::createFromJSON( $plan );
		}
		return $plans;
	}

	/**
	 * @param SubscriptionPlan $subscriptionPlan
	 * @return SubscriptionPlan
	 */
	public function createSubscriptionPlanForProduct( SubscriptionPlan $subscriptionPlan ): SubscriptionPlan {
		$response = $this->client->request(
			'POST',
			self::ENDPOINT_SUBSCRIPTION_PLANS,
			[
				RequestOptions::HEADERS => [
					'Authorization' => "Basic {$this->clientId}:{$this->clientSecret}",
					'Content-Type' => "application/json",
					'Accept' => "application/json",
					'Prefer' => 'return=representation'
				],
				RequestOptions::BODY => $subscriptionPlan->toJSON()
			]
		);

		$serverResponse = $response->getBody()->getContents();
		$jsonSubscriptionPlanResponse = $this->safelyDecodeJSON( $serverResponse );

		try {
			return SubscriptionPlan::createFromJSON( $jsonSubscriptionPlanResponse );
		} catch ( PayPalAPIException $e ) {
			throw $this->createLoggedException(
				"Server returned faulty subscription plan data: " . $e->getMessage(),
				[
					"serverResponse" => $serverResponse,
					"error" => $e->getMessage()
				],
				$e
			);
		}
	}

	/**
	 * @param string $serverResponse
	 *
	 * @return array decoded JSON
	 * @phpstan-ignore-next-line
	 */
	private function safelyDecodeJSON( string $serverResponse ): array {
		try {
			$decodedJSONResponse = json_decode( $serverResponse, true, 512, JSON_THROW_ON_ERROR );
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

		if ( !is_array( $decodedJSONResponse ) ) {
			throw new PayPalAPIException( 'array expected' );
		}

		return $decodedJSONResponse;
	}

}
