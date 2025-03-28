<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\RequestOptions;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Order;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\PayPalAPIException;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Subscription;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;

class GuzzlePaypalAPI implements PaypalAPI {

	private const ENDPOINT_PRODUCTS = '/v1/catalogs/products';
	private const ENDPOINT_SUBSCRIPTION_PLANS = '/v1/billing/plans';
	private const ENDPOINT_SUBSCRIPTION = '/v1/billing/subscriptions';
	private const ENDPOINT_ORDER = '/v2/checkout/orders';

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
	private function createLoggedException( string $errorMessage, array $context, ?\Exception $e = null ): PayPalAPIException {
		$this->logger->error( $errorMessage, $context );
		throw new PayPalAPIException( $errorMessage, 0, $e );
	}

	public function listProducts(): array {
		$productResponse = $this->client->request(
			'GET',
			self::ENDPOINT_PRODUCTS,
			[ RequestOptions::HEADERS => [
				'Authorization' => $this->getAuthHeader()
			] ]
		);

		$serverResponse = $productResponse->getBody()->getContents();
		$jsonProductResponse = $this->safelyDecodeJSON( $serverResponse );

		/** @phpstan-ignore-next-line function.alreadyNarrowedType */
		if ( !is_array( $jsonProductResponse ) || !isset( $jsonProductResponse['products'] ) ) {
			throw $this->createLoggedException( "Listing products failed!", [ "serverResponse" => $serverResponse ] );
		}

		/** @phpstan-ignore-next-line function.alreadyNarrowedType */
		if ( ( !is_array( $jsonProductResponse ) || isset( $jsonProductResponse['total_pages'] ) )
				&& $jsonProductResponse['total_pages'] > 1
			) {
			throw $this->createLoggedException(
				"Paging is not supported because we don't have that many products!",
				[ "serverResponse" => $serverResponse ]
			);
		}

		$products = [];
		if ( !is_array( $jsonProductResponse['products'] ) ) {
			throw $this->createLoggedException(
				"Products must be iterable!",
				[ "serverResponse" => $serverResponse ]
			);
		}
		foreach ( $jsonProductResponse['products'] as $product ) {
			/** @phpstan-ignore-next-line argument.type */
			$products[] = new Product( $product['id'], $product['name'], $product['description'] ?? '' );
		}
		return $products;
	}

	public function createProduct( Product $product ): Product {
		$response = $this->sendPOSTRequest( self::ENDPOINT_PRODUCTS, $product->toJSON() );

		$serverResponse = $response->getBody()->getContents();
		$jsonProductResponse = $this->safelyDecodeJSON( $serverResponse );

		if ( empty( $jsonProductResponse['name'] ) || empty( $jsonProductResponse['id'] ) ) {
			throw $this->createLoggedException(
				'Server did not send product data back',
				[ "serverResponse" => $serverResponse ]
			);
		}
		return new Product(
			$jsonProductResponse['id'],
			$jsonProductResponse['name'],
			$jsonProductResponse['description'] ?? null
		);
	}

	public function listSubscriptionPlansForProduct( string $productId ): array {
		$planResponse = $this->client->request(
			'GET',
			self::ENDPOINT_SUBSCRIPTION_PLANS,
			[
				RequestOptions::HEADERS => [
					'Authorization' => $this->getAuthHeader(),
					'Accept' => "application/json",
					'Prefer' => 'return=representation'
				],
				RequestOptions::QUERY => [ 'product_id' => $productId ]
			]
		);

		$serverResponse = $planResponse->getBody()->getContents();
		$jsonPlanResponse = $this->safelyDecodeJSON( $serverResponse );

		/** @phpstan-ignore-next-line function.alreadyNarrowedType */
		if ( !is_array( $jsonPlanResponse ) || !isset( $jsonPlanResponse['plans'] ) ) {
			throw $this->createLoggedException( "Listing subscription plans failed!", [ "serverResponse" => $serverResponse ] );
		}

		if ( isset( $jsonPlanResponse['total_pages'] ) && $jsonPlanResponse['total_pages'] > 1 ) {
			throw $this->createLoggedException(
				"Paging is not supported because each product should not have more than 4 payment intervals!",
				[ "serverResponse" => $serverResponse ]
			);
		}

		$plans = [];
		if ( !is_iterable( $jsonPlanResponse['plans'] ) ) {
			throw $this->createLoggedException(
				"Plans must be iterable!",
				[ "serverResponse" => $serverResponse ]
			);
		}
		foreach ( $jsonPlanResponse['plans'] as $plan ) {
			$plans[] = SubscriptionPlan::from( $plan );
		}
		return $plans;
	}

	/**
	 * @param SubscriptionPlan $subscriptionPlan
	 * @return SubscriptionPlan
	 */
	public function createSubscriptionPlanForProduct( SubscriptionPlan $subscriptionPlan ): SubscriptionPlan {
		$response = $this->sendPOSTRequest( self::ENDPOINT_SUBSCRIPTION_PLANS, $subscriptionPlan->toJSON() );

		$serverResponse = $response->getBody()->getContents();
		$jsonSubscriptionPlanResponse = $this->safelyDecodeJSON( $serverResponse );

		try {
			return SubscriptionPlan::from( $jsonSubscriptionPlanResponse );
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

	public function createSubscription( SubscriptionParameters $subscriptionParameters ): Subscription {
		$response = $this->sendPOSTRequest( self::ENDPOINT_SUBSCRIPTION, $subscriptionParameters->toJSON() );

		$serverResponse = $response->getBody()->getContents();
		$jsonSubscriptionResponse = $this->safelyDecodeJSON( $serverResponse );

		return Subscription::from( $jsonSubscriptionResponse );
	}

	public function createOrder( OrderParameters $orderParameters ): Order {
		$response = $this->sendPOSTRequest( self::ENDPOINT_ORDER, $orderParameters->toJSON() );

		$serverResponse = $response->getBody()->getContents();
		$jsonOrderResponse = $this->safelyDecodeJSON( $serverResponse );

		return Order::from( $jsonOrderResponse );
	}

	private function sendPOSTRequest( string $endpointURI, string $requestBody ): ResponseInterface {
		try {
			return $this->client->request(
				'POST',
				$endpointURI,
				[
					RequestOptions::HEADERS => [
						'Authorization' => $this->getAuthHeader(),
						'Content-Type' => "application/json",
						'Accept' => "application/json",
						'Prefer' => 'return=representation'
					],
					RequestOptions::BODY => $requestBody
				]
			);
		} catch ( BadResponseException $e ) {
			throw $this->createLoggedException(
				"Server rejected request: " . $e->getMessage(),
				[
					"serverResponse" => $e->getResponse()->getBody()->getContents(),
					"error" => $e->getMessage(),
					"requestBody" => $requestBody
				],
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

	private function getAuthHeader(): string {
		return 'Basic ' . base64_encode( $this->clientId . ':' . $this->clientSecret );
	}

}
