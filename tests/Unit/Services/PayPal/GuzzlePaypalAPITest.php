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

	public function testWhenListProductsIsCalledWeAuthenticate(): void {
		$accessToken = 'A21AAFEpH4PsADK7qSS7pSRsgzfENtu-Q1ysgEDVDESseMHBYXVJYE8ovjj68elIDy8nF26AwPhfXTIeWAZHSLIsQkSYz9ifg';
		$mock = new MockHandler( [
			new Response(
				200,
				[],
				<<<RESPONSE
{
  "scope": "https://uri.paypal.com/services/invoicing https://uri.paypal.com/services/disputes/read-buyer https://uri.paypal.com/services/payments/realtimepayment https://uri.paypal.com/services/disputes/update-seller https://uri.paypal.com/services/payments/payment/authcapture openid https://uri.paypal.com/services/disputes/read-seller https://uri.paypal.com/services/payments/refund https://api-m.paypal.com/v1/vault/credit-card https://api-m.paypal.com/v1/payments/.* https://uri.paypal.com/payments/payouts https://api-m.paypal.com/v1/vault/credit-card/.* https://uri.paypal.com/services/subscriptions https://uri.paypal.com/services/applications/webhooks",
  "access_token": "$accessToken",
  "token_type": "Bearer",
  "app_id": "APP-80W284485P519543T",
  "expires_in": 31668,
  "nonce": "2020-04-03T15:35:36ZaYZlGvEkV4yVSz8g6bAKFoGSEzuy3CQcz3ljhibkOHg"
}
RESPONSE
			),
			new Response(
				200,
				[],
				<<<RESPONSE
{
  "total_items": 0,
  "total_pages": 0,
  "products": []
}
RESPONSE
			)
		] );

		$container = [];
		$history = Middleware::history( $container );
		$handlerStack = HandlerStack::create( $mock );
		$handlerStack->push( $history );

		$client = new Client( [ 'handler' => $handlerStack ] );

		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword' );

		$guzzlePaypalApi->listProducts();

		$this->assertCount( 2, $container, 'We expect an auth request and a list request' );

		/** @var Request $authRequest */
		$authRequest = $container[ 0 ][ 'request' ];
		$listRequest = $container[ 1 ][ 'request' ];
		$this->assertSame(
			'Basic ' . base64_encode( 'testUserName:testPassword' ),
			$authRequest->getHeaderLine( 'authorization' )
		);
		$this->assertSame( 'application/x-www-form-urlencoded', $authRequest->getHeaderLine( 'Content-Type' ) );
		$this->assertSame( 'grant_type=client_credentials', $authRequest->getBody()->getContents() );

		// see request in https://developer.paypal.com/api/rest/authentication/
		$this->assertSame( "Bearer $accessToken", $listRequest->getHeaderLine( 'Authorization' ) );
	}

	public function testWhenApiReturnsMalformedJsonThrowException(): void {
		$mock = new MockHandler( [
			new Response(
				200,
				[],
				<<<RESPONSE
					{"sss_reserved": "0",
RESPONSE
			)
		] );

		$handlerStack = HandlerStack::create( $mock );

		$client = new Client( [ 'handler' => $handlerStack ] );

		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword' );

		$this->expectException( PayPalAPIException::class );

		$guzzlePaypalApi->listProducts();
	}

	public function testWhenApiReturnsJSONWithUnexpectedKeys(): void {
		$mock = new MockHandler( [
			new Response(
				200,
				[],
				<<<RESPONSE
					{"I'M NOT AN AUTH KEY": "0" }
RESPONSE
			)
		] );

		$handlerStack = HandlerStack::create( $mock );

		$client = new Client( [ 'handler' => $handlerStack ] );

		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword' );

		$this->expectException( PayPalAPIException::class );

		$guzzlePaypalApi->listProducts();
	}

	// TODO test authentication failure

	// TODO test that auth URL request is only called once on multiple calls to listProducts

	// TODO test that response returns list of products

	// TODO test that API throws exception if response indicates multiple pages (we only have 2 products and don't want to implement paging)

}
