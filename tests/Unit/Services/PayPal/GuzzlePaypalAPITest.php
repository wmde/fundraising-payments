<?php

namespace WMDE\Fundraising\PaymentContext\Tests\Unit\Services\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use WMDE\Fundraising\PaymentContext\Services\PayPal\GuzzlePaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\GuzzlePaypalAPI
 */
class GuzzlePaypalAPITest extends TestCase {

	/**
	 * @var array<int,array<string,mixed>>
	 */
	private array $guzzleHistory;

	protected function setUp(): void {
		$this->guzzleHistory = [];
	}

	public function testListProductsSendsCredentials(): void {
		$client = $this->givenClientWithResponses(
			$this->createEmptyProductResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

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
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( $malformedJsonResponse ),
			'testUserName',
			'testPassword',
			new NullLogger()
		);

		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessageMatches( "/Malformed JSON/" );

		$guzzlePaypalApi->listProducts();
	}

	public function testWhenApiReturnsJSONWithUnexpectedKeys(): void {
		$responseWithoutAuthToken = new Response(
			200,
			[],
			'{"error": "access denied" }'
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( $responseWithoutAuthToken ),
			'testUserName',
			'testPassword',
			new NullLogger()
		);

		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessageMatches( "/Listing products failed!/" );

		$guzzlePaypalApi->listProducts();
	}

	public function testListProductsReturnsListOfProducts(): void {
		$client = $this->givenClientWithResponses(
			$this->createProductResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$actualProducts = $guzzlePaypalApi->listProducts();

		$this->assertEquals(
			[ new Product( 'WMDE_Donation', 'ID-1', 'Description' ), new Product( 'WMDE_Membership', 'ID-2', null ) ],
			$actualProducts
		);
	}

	public function testListProductsReturnsNoProductsWhenServerResponseContainsNoProducts(): void {
		$client = $this->givenClientWithResponses(
			$this->createEmptyProductResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$actualProducts = $guzzlePaypalApi->listProducts();

		$this->assertEquals(
			[],
			$actualProducts
		);
	}

	/**
	 * we only have 2 products and don't want to implement paging
	 * @return void
	 */
	public function testWhenServerIndicatesMultiplePagesOfProductsExceptionIsThrown(): void {
		$client = $this->givenClientWithResponses(
			$this->createTooManyProductPagesResponse()
		);

		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$this->expectException( PayPalAPIException::class );
		$this->expectExceptionMessageMatches( "/Paging is not supported because we don't have that many products!/" );

		$guzzlePaypalApi->listProducts();
	}

	private function givenClientWithResponses( Response ...$responses ): Client {
		$mock = new MockHandler( array_values( $responses ) );
		$history = Middleware::history( $this->guzzleHistory );
		$handlerStack = HandlerStack::create( $mock );
		$handlerStack->push( $history );

		return new Client( [ 'handler' => $handlerStack ] );
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

	private function createTooManyProductPagesResponse(): Response {
		return new Response(
			200,
			[],
			<<<RESPONSE
{
  "total_items": 44444,
  "total_pages": 2,
  "products": []
}
RESPONSE
		);
	}

	private function createProductResponse(): Response {
		return new Response(
			200,
			[],
			<<<RESPONSE
			{
  "total_items": 2,
  "total_pages": 1,
  "products": [
    {
		"id": "ID-1",
     	"name": "WMDE_Donation",
      	"description": "Description",
      	"create_time": "2023-12-10T21:20:49Z",
      	"links": [
        	{
				"href": "https://api-m.paypal.com/v1/catalogs/products/72255d4849af8ed6e0df1173",
          		"rel": "self",
          		"method": "GET"
        	}
      	]
    },
    {
		"id": "ID-2",
     	"name": "WMDE_Membership",
      	"create_time": "2018-12-10T21:20:49Z",
      	"links": [
        {
			"href": "https://api-m.paypal.com/v1/catalogs/products/125d4849af8ed6e0df18",
         	"rel": "self",
          	"method": "GET"
        }
      ]
    }
  ],
  "links": [
    {
		"href": "https://api-m.paypal.com/v1/catalogs/products?page_size=2&page=1",
      	"rel": "self",
      	"method": "GET"
    },
    {
		"href": "https://api-m.paypal.com/v1/catalogs/products?page_size=2&page=2",
	     "rel": "next",
     	"method": "GET"
    },
    {
		"href": "https://api-m.paypal.com/v1/catalogs/products?page_size=2&page=10",
      	"rel": "last",
      	"method": "GET"
    }
  ]
}
RESPONSE
		);
	}
}
