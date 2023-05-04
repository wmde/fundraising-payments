<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\PayPal\GuzzlePaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\GuzzlePaypalAPI
 */
class GuzzlePaypalAPITest extends TestCase {

	private const ACCESS_TOKEN = 'A21AAFEpH4PsADK7qSS7pSRsgzfENtu-Q1ysgEDVDESseMHBYXVJYE8ovjj68elIDy8nF26AwPhfXTIeWAZHSLIsQkSYz9ifg';

	private array $guzzleHistory;

	protected function setUp(): void {
		$this->guzzleHistory = [];
	}

	public function testListProductsSendsCredentials(): void {
		$client = $this->givenClientWithResponses(
			$this->createEmptyProductResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword' );

		$guzzlePaypalApi->listProducts();

		$this->assertCount( 1, $this->guzzleHistory, 'We expect a list request' );
		/** @var Request $listRequest */
		$listRequest = $this->guzzleHistory[ 0 ][ 'request' ];
		$this->assertSame(
			'Basic testUserName:testPassword',
			$listRequest->getHeaderLine( 'authorization' )
		);
	}

	public function testWhenApiReturnsMalformedJsonThrowException(): void {
		$malformedJsonResponse = new Response(
			200,
			[],
			'{"sss_reserved": "0"'
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $this->givenClientWithResponses( $malformedJsonResponse ), 'testUserName', 'testPassword' );

		$this->expectException( PayPalAPIException::class );

		$guzzlePaypalApi->listProducts();
	}

	public function testWhenApiReturnsJSONWithUnexpectedKeys(): void {
		$responseWithoutAuthToken = new Response(
			200,
			[],
			'{"error": "access denied" }'
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $this->givenClientWithResponses( $responseWithoutAuthToken ), 'testUserName', 'testPassword' );

		$this->expectException( PayPalAPIException::class );

		$guzzlePaypalApi->listProducts();
	}

	public function testListProductsReturnsListOfProducts(): void {
		$this->markTestIncomplete( "TODO" );
	}

	public function testListProductsReturnsNoProductsWhenServerResponseContainsNoProducts(): void {
		$this->markTestIncomplete( "TODO" );
	}

	public function testWhenServerIndicatesMultiplePagesOfProductsExceptionIsThrown(): void {
		// we only have 2 products and don't want to implement paging
		$this->markTestIncomplete( "TODO" );
	}

	private function givenClientWithResponses( Response ...$responses ): Client {
		$mock = new MockHandler( $responses );
		$history = Middleware::history( $this->guzzleHistory );
		$handlerStack = HandlerStack::create( $mock );
		$handlerStack->push( $history );

		return new Client( [ 'handler' => $handlerStack ] );
	}

	private function createSuccessfulAuthResponse(): Response {
		$accessToken = self::ACCESS_TOKEN;
		return new Response(
			200,
			[],
			<<<RESPONSE
{
  "scope": "https://uri.paypal.com/services/invoicing https://uri.paypal.com/services/disputes/read-buyer https://uri.paypal.com/services/payments/realtimepayment https://uri.paypal.com/services/disputes/update-seller https://uri.paypal.com/services/payments/payment/authcapture openid https://uri.paypal.com/services/disputes/read-seller https://uri.paypal.com/services/payments/refund https://api-m.paypal.com/v1/vault/credit-card https://api-m.paypal.com/v1/payments/.* https://uri.paypal.com/payments/payouts https://api-m.paypal.com/v1/vault/credit-card/.* https://uri.paypal.com/services/subscriptions https://uri.paypal.com/services/applications/webhooks",
  "access_token": "{$accessToken}",
  "token_type": "Bearer",
  "app_id": "APP-80W284485P519543T",
  "expires_in": 31668,
  "nonce": "2020-04-03T15:35:36ZaYZlGvEkV4yVSz8g6bAKFoGSEzuy3CQcz3ljhibkOHg"
}
RESPONSE
		);
	}

	private function createEmptyProductResponse(): Response {
		return new Response(
			200,
			[],
			<<<RESPONSE
{
  "total_items": 0,
  "total_pages": 0,
  "products": []
}
RESPONSE
		);
	}

}
