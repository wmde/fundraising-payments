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
use RuntimeException;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Services\PayPal\GuzzlePaypalAPI;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\Product;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters;
use WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionPlan;
use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;
use WMDE\PsrLogTestDoubles\LoggerSpy;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\GuzzlePaypalAPI
 */
class GuzzlePaypalAPITest extends TestCase {

	/**
	 * This is the base64 encoded value of 'testUsername:testPassword'
	 */
	private const BASIC_AUTH_HEADER = 'Basic dGVzdFVzZXJOYW1lOnRlc3RQYXNzd29yZA==';

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
			self::BASIC_AUTH_HEADER,
			$listRequest->getHeaderLine( 'authorization' )
		);
	}

	public function testWhenApiReturnsMalformedJsonThrowException(): void {
		$logger = new LoggerSpy();
		$responseBody = '{"sss_reserved": "0"';
		$malformedJsonResponse = new Response( 200, [], $responseBody );
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( $malformedJsonResponse ),
			'testUserName',
			'testPassword',
			$logger
		);

		try {
			$guzzlePaypalApi->listProducts();
			$this->fail( 'listProducts should throw an exception' );
		} catch ( PayPalAPIException $e ) {
			$this->assertJSONException( $e, $logger, $responseBody );
		}
	}

	public function testWhenApiReturnsJSONWithUnexpectedKeysLogServerResponseAndThrowException(): void {
		$logger = new LoggerSpy();
		$responseBody = '{"error": "access denied" }';
		$responseWithoutAuthToken = new Response( 200, [], $responseBody );
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( $responseWithoutAuthToken ),
			'testUserName',
			'testPassword',
			$logger
		);

		try {
			$guzzlePaypalApi->listProducts();
			$this->fail( 'listProducts should throw an exception' );
		} catch ( PayPalAPIException $e ) {
			$this->assertStringContainsString( "Listing products failed", $e->getMessage() );
			$firstCall = $logger->getFirstLogCall();
			$this->assertNotNull( $firstCall );
			$this->assertStringContainsString( "Listing products failed", $firstCall->getMessage() );
			$this->assertArrayHasKey( 'serverResponse', $firstCall->getContext() );
			$this->assertSame( $responseBody, $firstCall->getContext()['serverResponse'] );
		}
	}

	public function testListProductsReturnsListOfProducts(): void {
		$client = $this->givenClientWithResponses(
			$this->createProductResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$actualProducts = $guzzlePaypalApi->listProducts();

		$this->assertEquals(
			[
				new Product( 'ID-1', 'WMDE_Donation', 'Description' ),
				new Product( 'ID-2', 'WMDE_Membership', null )
			],
			$actualProducts
		);
		/** @var Request $createRequest */
		$createRequest = $this->guzzleHistory[0]['request'];
		$this->assertSame( 'GET', $createRequest->getMethod() );
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
		$logger = new LoggerSpy();
		$responseBody = <<<RESPONSE
			{
			  "total_items": 44444,
			  "total_pages": 2,
			  "products": []
			}
RESPONSE;
		$response = new Response( 200, [], $responseBody );
		$client = $this->givenClientWithResponses( $response );

		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', $logger );

		try {
			$guzzlePaypalApi->listProducts();
			$this->fail( 'listProducts should throw an exception' );
		} catch ( PayPalAPIException $e ) {
			$this->assertStringContainsString( "Paging is not supported because we don't have that many products", $e->getMessage() );
			$firstCall = $logger->getFirstLogCall();
			$this->assertNotNull( $firstCall );
			$this->assertStringContainsString( "Paging is not supported because we don't have that many products", $firstCall->getMessage() );
			$this->assertArrayHasKey( 'serverResponse', $firstCall->getContext() );
			$this->assertSame( $responseBody, $firstCall->getContext()['serverResponse'] );
		}
	}

	public function testCreateProductSendsProductData(): void {
		$responseBody = <<<RESPONSE
			{
				"id": "someSpecificID",
				"name": "WMDE_FUNNYDonation",
				"description": "WMDE_FUNNYDonationDescription",
				"type": "SERVICE",
				"category": "NONPROFIT",
				"create_time": "2019-01-10T21:20:49Z",
				"update_time": "2019-01-10T21:20:49Z"
			}
RESPONSE;
		$response = new Response( 200, [], $responseBody );
		$client = $this->givenClientWithResponses( $response );
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );
		$product = new Product( "someSpecificID", 'WMDE_FUNNYDonation', 'WMDE_FUNNYDonationDescription' );

		$guzzlePaypalApi->createProduct( $product );

		$this->assertCount( 1, $this->guzzleHistory, 'We expect a create request' );
		$expectedRequestBody = <<<REQUEST
{
"name": "WMDE_FUNNYDonation",
"id": "someSpecificID",
"description": "WMDE_FUNNYDonationDescription",
"category": "NONPROFIT",
"type": "SERVICE"
}
REQUEST;
		/** @var Request $createRequest */
		$createRequest = $this->guzzleHistory[ 0 ][ 'request' ];

		$this->assertSame( 'POST', $createRequest->getMethod() );
		$this->assertSame( self::BASIC_AUTH_HEADER, $createRequest->getHeaderLine( 'authorization' ) );
		$this->assertSame(
			json_encode( json_decode( $expectedRequestBody ) ),
			$createRequest->getBody()->getContents()
		);
		$this->assertSame( 'application/json', $createRequest->getHeaderLine( 'Content-Type' ) );
		$this->assertSame( 'application/json', $createRequest->getHeaderLine( 'Accept' ) );
	}

	public function testNewProductIsCreatedFromServerData(): void {
		// The server response here has different values on purpose to make sure that all values come from the server
		// In reality, the PayPal server should *never* change our values, only generate IDs if they are missing
		$responseBody = <<<RESPONSE
			{
				"id": "ServerId",
				"name": "ServerDonation",
				"description": "ServerDescription",
				"type": "SERVICE",
				"category": "NONPROFIT",
				"create_time": "2019-01-10T21:20:49Z",
				"update_time": "2019-01-10T21:20:49Z"
			}
RESPONSE;
		$response = new Response( 200, [], $responseBody );
		$client = $this->givenClientWithResponses( $response );
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );
		$product = new Product( 'FD1', 'WMDE_FUNNYDonation', );

		$createdProduct = $guzzlePaypalApi->createProduct( $product );

		$this->assertNotSame( $product, $createdProduct, 'method should create a new product from server data' );
		$this->assertSame( 'ServerId', $createdProduct->id );
		$this->assertSame( 'ServerDonation', $createdProduct->name );
		$this->assertSame( 'ServerDescription', $createdProduct->description );
	}

	public function testCreateProductFailsWhenServerResponseHasMalformedJson(): void {
		$logger = new LoggerSpy();
		$responseBody = '{"sss_reserved": "0"';
		$malformedJsonResponse = new Response( 200, [], $responseBody );
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( $malformedJsonResponse ),
			'testUserName',
			'testPassword',
			$logger
		);

		try {
			$guzzlePaypalApi->createProduct( new Product( 'D1', 'Dummy', ) );
			$this->fail( 'createProduct should throw an exception' );
		} catch ( PayPalAPIException $e ) {
			$this->assertJSONException( $e, $logger, $responseBody );
		}
	}

	public function testCreateProductFailsWhenServerResponseDoesNotContainProductData(): void {
		$logger = new LoggerSpy();
		$responseBody = '{"error": "access denied" }';
		$jsonResponseWithErrors = new Response( 200, [], $responseBody );
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( $jsonResponseWithErrors ),
			'testUserName',
			'testPassword',
			$logger
		);

		try {
			$guzzlePaypalApi->createProduct( new Product( 'D1', 'Dummy', ) );
			$this->fail( 'createProduct should throw an exception' );
		} catch ( PayPalAPIException $e ) {
			$this->assertStringContainsString( "Server did not send product data back", $e->getMessage() );
			$firstCall = $logger->getFirstLogCall();
			$this->assertNotNull( $firstCall );
			$this->assertStringContainsString( "Server did not send product data back", $firstCall->getMessage() );
			$this->assertArrayHasKey( 'serverResponse', $firstCall->getContext() );
			$this->assertSame( $responseBody, $firstCall->getContext()['serverResponse'] );
		}
	}

	public function testListSubscriptionPlansSendsCredentials(): void {
		$client = $this->givenClientWithResponses(
			$this->createEmptyPlanResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$guzzlePaypalApi->listSubscriptionPlansForProduct( 'donation' );

		$this->assertCount( 1, $this->guzzleHistory, 'We expect a list request' );
		/** @var Request $listRequest */
		$listRequest = $this->guzzleHistory[ 0 ][ 'request' ];
		$this->assertSame(
			self::BASIC_AUTH_HEADER,
			$listRequest->getHeaderLine( 'authorization' )
		);
	}

	public function testListSubscriptionPlansQueriesOnlyRequestedProducts(): void {
		$client = $this->givenClientWithResponses(
			$this->createEmptyPlanResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$guzzlePaypalApi->listSubscriptionPlansForProduct( 'donation' );

		$this->assertCount( 1, $this->guzzleHistory, 'We expect a list request' );
		/** @var Request $listRequest */
		$listRequest = $this->guzzleHistory[ 0 ][ 'request' ];
		$this->assertSame(
			'product_id=donation',
			$listRequest->getUri()->getQuery()
		);
	}

	public function testListSubscriptionPlansReturnsSubscriptions(): void {
		$client = $this->givenClientWithResponses(
			$this->createSubscriptionsResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$plans = $guzzlePaypalApi->listSubscriptionPlansForProduct( 'donation' );

		$this->assertCount( 2, $plans );
		$this->assertSame( 'P-0HVWVNKK2LCV2VN57N79TLENELT78EKL', $plans[0]->id );
		$this->assertEquals( 'monthly donation', $plans[0]->name );
	}

	public function testListSubscriptionPlansThrowsErrorOnMalformedJSON(): void {
		$responseBody = 'br0ken';
		$client = $this->givenClientWithResponses(
			new Response( 200, [], $responseBody )
		);
		$loggerSpy = new LoggerSpy();
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', $loggerSpy );

		try {
			$guzzlePaypalApi->listSubscriptionPlansForProduct( 'donation' );
			$this->fail( 'It should throw an Exception.' );
		} catch ( PayPalAPIException $e ) {
			$this->assertJSONException( $e, $loggerSpy, $responseBody );
		}
	}

	public function testListSubscriptionPlansThrowsErrorOnMissingPlansProperty(): void {
		$client = $this->givenClientWithResponses(
			$this->createUndefinedPlansPropertyResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$this->expectExceptionMessage( 'Malformed JSON' );
		$guzzlePaypalApi->listSubscriptionPlansForProduct( 'donation' );
	}

	public function testListSubscriptionPlansThrowsErrorOnPagePropertyBiggerThanOne(): void {
		$client = $this->givenClientWithResponses(
			$this->createMultiplePagesResponse()
		);
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );

		$this->expectExceptionMessage( 'Paging is not supported because each product should not have more than 4 payment intervals!' );
		$guzzlePaypalApi->listSubscriptionPlansForProduct( 'donation' );
	}

	public function testCreatesSubscriptionPlansForAProduct(): void {
		$response = $this->createCreateSubscriptionPlanResponse();
		$client = $this->givenClientWithResponses( $response );
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );
		$testPlan = new SubscriptionPlan( 'monthly', 'ServerPRODUCT-42', PaymentInterval::Monthly );

		$createdPlan = $guzzlePaypalApi->createSubscriptionPlanForProduct( $testPlan );

		$this->assertNotSame( $testPlan, $createdPlan, 'method should create a new subscription plan from server data' );
		$this->assertSame( 'ABCD-SERVER-GENERATED', $createdPlan->id, );
		$this->assertSame( 'ServerMonthly', $createdPlan->name );
		$this->assertSame( 'ServerPRODUCT-42', $createdPlan->productId );
	}

	public function testCreateSubscriptionForProductFailsWhenServerResponseHasMalformedJson(): void {
		$logger = new LoggerSpy();
		$responseBody = '{"sss_reserved": "0"';
		$malformedJsonResponse = new Response( 200, [], $responseBody );
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( $malformedJsonResponse ),
			'testUserName',
			'testPassword',
			$logger
		);

		try {
			$guzzlePaypalApi->createSubscriptionPlanForProduct( new SubscriptionPlan( 'Dummy', 'D1', PaymentInterval::HalfYearly ) );
			$this->fail( 'createSubscriptionPlanForProduct should throw an exception' );
		} catch ( PayPalAPIException $e ) {
			$this->assertJSONException( $e, $logger, $responseBody );
		}
	}

	private function assertJSONException( PayPalAPIException $e, LoggerSpy $logger, string $responseBody ): void {
		$this->assertStringContainsString( "Malformed JSON", $e->getMessage() );
		$firstCall = $logger->getFirstLogCall();
		$this->assertNotNull( $firstCall );
		$this->assertStringContainsString( "Malformed JSON", $firstCall->getMessage() );
		$this->assertArrayHasKey( 'serverResponse', $firstCall->getContext() );
		$this->assertSame( $responseBody, $firstCall->getContext()['serverResponse'] );
	}

	public function testCreateSubscriptionForProductFailsWhenServerResponseDoesNotContainSubscriptionData(): void {
		$logger = new LoggerSpy();
		$responseBody = '{"error": "access denied" }';
		$malformedJsonResponse = new Response( 200, [], $responseBody );
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( $malformedJsonResponse ),
			'testUserName',
			'testPassword',
			$logger
		);

		try {
			$guzzlePaypalApi->createSubscriptionPlanForProduct( new SubscriptionPlan( 'Dummy', 'D1', PaymentInterval::HalfYearly ) );
			$this->fail( 'createSubscriptionPlanForProduct should throw an exception' );
		} catch ( PayPalAPIException $e ) {
			$this->assertStringContainsString( "Server returned faulty subscription plan data", $e->getMessage() );
			$firstCall = $logger->getFirstLogCall();
			$this->assertNotNull( $firstCall );
			$this->assertStringContainsString( "Server returned faulty subscription plan data", $firstCall->getMessage() );
			$this->assertArrayHasKey( 'serverResponse', $firstCall->getContext() );
			$this->assertSame( $responseBody, $firstCall->getContext()['serverResponse'] );
		}
	}

	/**
	 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\Model\SubscriptionParameters
	 */
	public function testCreateSubscriptionBuildsJsonRequestFromSubscriptionParameters(): void {
		$response = $this->createCreateSubscriptionResponse();
		$client = $this->givenClientWithResponses( $response );
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );
		$testPlan = new SubscriptionPlan( 'monthly', 'ServerPRODUCT-42', PaymentInterval::Monthly, 'P-5ML4271244454362WXNWU5NQ' );

		$fixedTimeStamp = new \DateTimeImmutable( '2018-11-01T00:00:00Z' );

		$guzzlePaypalApi->createSubscription(
			new SubscriptionParameters(
				$testPlan,
				Euro::newFromCents( 4223 ),
				'https://example.com/returnUrl',
				'https://example.com/cancelUrl',
				$fixedTimeStamp
			)
		);

		$this->assertCount( 1, $this->guzzleHistory, 'We expect a create subscription request' );

		/** @var Request $createRequest */
		$createRequest = $this->guzzleHistory[ 0 ][ 'request' ];

		$this->assertSame( 'POST', $createRequest->getMethod() );
		$this->assertSame( self::BASIC_AUTH_HEADER, $createRequest->getHeaderLine( 'authorization' ) );
		$this->assertSame(
			json_encode( json_decode( $this->readTestFixture( 'create_subscription_request.json' ) ), JSON_PRETTY_PRINT ),
			json_encode( json_decode( $createRequest->getBody()->getContents() ), JSON_PRETTY_PRINT )
		);
		$this->assertSame( 'application/json', $createRequest->getHeaderLine( 'Content-Type' ) );
		$this->assertSame( 'application/json', $createRequest->getHeaderLine( 'Accept' ) );
	}

	/**
	 * @covers \WMDE\Fundraising\PaymentContext\Services\PayPal\Model\OrderParameters
	 */
	public function testCreateOrderBuildsJsonRequestFromOrderParameters(): void {
		$response = $this->createCreateOrderResponse();
		$client = $this->givenClientWithResponses( $response );
		$guzzlePaypalApi = new GuzzlePaypalAPI( $client, 'testUserName', 'testPassword', new NullLogger() );
		$guzzlePaypalApi->createOrder(
			new OrderParameters(
				"D-78945ABCD",
				"78945ABCD",
				"Spende an Wikimedia Deutschland",
				Euro::newFromCents( 4223 ),
				'https://example.com/returnUrl',
				'https://example.com/cancelUrl'
			)
		);

		$this->assertCount( 1, $this->guzzleHistory, 'We expect a create order request' );

		/** @var Request $createRequest */
		$createRequest = $this->guzzleHistory[ 0 ][ 'request' ];

		$this->assertSame( 'POST', $createRequest->getMethod() );
		$this->assertSame( self::BASIC_AUTH_HEADER, $createRequest->getHeaderLine( 'authorization' ) );
		$this->assertSame(
			json_encode( json_decode( $this->readTestFixture( 'create_order_request.json' ) ), JSON_PRETTY_PRINT ),
			json_encode( json_decode( $createRequest->getBody()->getContents() ), JSON_PRETTY_PRINT )
		);
		$this->assertSame( 'application/json', $createRequest->getHeaderLine( 'Content-Type' ) );
		$this->assertSame( 'application/json', $createRequest->getHeaderLine( 'Accept' ) );
	}

	public function testApiConvertsBadResponseIntoApiException(): void {
		$logger = new LoggerSpy();
		$guzzlePaypalApi = new GuzzlePaypalAPI(
			$this->givenClientWithResponses( new Response( 400, [], 'No Cookies or cupcakes in request! Cookie monster sad!' ) ),
			'testUserName',
			'testPassword',
			$logger
		);

		try {
			$guzzlePaypalApi->createProduct( new Product( 'D1', 'Dummy', ) );
			$this->fail( 'createProduct should throw an exception' );
		} catch ( PayPalAPIException $e ) {
			$this->assertStringContainsString( 'Server rejected request', $e->getMessage() );
			$log = $logger->getFirstLogCall();
			$this->assertNotNull( $log );
			$context = $log->getContext();
			$this->assertArrayHasKey( 'serverResponse', $context );
			$this->assertArrayHasKey( 'error', $context );
			$this->assertArrayHasKey( 'requestBody', $context );
		}
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

	private function createEmptyPlanResponse(): Response {
		return new Response(
			200,
			[],
			<<<RESPONSE
			{
  				"total_items": 0,
  				"plans": []
			}
RESPONSE
		);
	}

	private function createSubscriptionsResponse(): Response {
		return new Response( 200, [], $this->readTestFixture( 'list_plans_response.json' ) );
	}

	private function readTestFixture( string $fileName ): string {
		$validJSONResponseContent = file_get_contents( __DIR__ . '/../../../Data/PaypalAPI/' . $fileName );
		if ( $validJSONResponseContent === false ) {
			throw new RuntimeException( ' could not read fixture file ' . __DIR__ . '/../../../Data/PaypalAPI/' . $fileName );
		}
		return $validJSONResponseContent;
	}

	private function createCreateSubscriptionPlanResponse(): Response {
		return new Response( 200, [], $this->readTestFixture( 'create_plans_response.json' ) );
	}

	private function createUndefinedPlansPropertyResponse(): Response {
		return new Response(
			200,
			[],
			<<<RESPONSE
			{
  				"total_items": 0,
			}
RESPONSE
		);
	}

	private function createMultiplePagesResponse(): Response {
		return new Response(
			200,
			[],
			<<<RESPONSE
			{
  				"total_items": 0,
  				"total_pages": 42,
  				"plans": []
			}
RESPONSE
		);
	}

	private function createCreateSubscriptionResponse(): Response {
		// Minimum response, the server will send more information
		return new Response(
			200,
			[],
			<<<RESPONSE
			{
				"id": "12345",
				"start_time": "2023-12-24T01:02:03Z",
				"links": [
					{
					
					    "href": "https://www.paypal.com/webapps/billing/subscriptions?ba_token=BA-2M539689T3856352J",
					    "rel": "approve",
					    "method": "GET"
					}
				]
			}
RESPONSE
		);
	}

	private function createCreateOrderResponse(): Response {
		// Minimum response, the server will send more information
		return new Response(
			200,
			[],
			<<<RESPONSE
			{
				"id": "78945",
				"links": [
					{
					
					    "href": "https://www.paypal.com/checkoutnow?token=5O190127TN364715T",
					    "rel": "payer-action",
					    "method": "GET"
					}
				]
			}
RESPONSE
		);
	}

}
