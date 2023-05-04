<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Tests\System\Services\ExternalVerificationService\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\PayPal\PayPalVerificationService;

/**
 * @covers \WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\PayPal\PayPalVerificationService
 */
class PayPalVerificationServiceTest extends TestCase {

	private const VALID_ACCOUNT_EMAIL = 'foerderpp@wikimedia.de';
	private const INVALID_ACCOUNT_EMAIL = 'this.is.not@my.email.address';
	private const DUMMY_API_URL = 'https://dummy-url.com';
	private const VALID_PAYMENT_STATUS = 'Completed';
	private const INVALID_PAYMENT_STATUS = 'Unknown';
	private const ITEM_NAME = 'My donation';
	private const CURRENCY_EUR = 'EUR';
	private const RECURRING_NO_PAYMENT = 'recurring_payment_suspended_due_to_max_failed_payment';

	/**
	 * @var array<string,mixed>
	 */
	private array $expectedRequestParameters;

	/**
	 * @var array<int,array<string,mixed>>
	 */
	private array $receivedRequests;

	protected function setUp(): void {
		$this->expectedRequestParameters = [];
		$this->receivedRequests = [];
	}

	public function testReceiverAddressMismatches_returnsFailureResponse(): void {
		$response = $this->makeVerificationService( new Client() )->validate( [
			'receiver_email' => self::INVALID_ACCOUNT_EMAIL
		] );

		$this->assertEquals( PayPalVerificationService::ERROR_WRONG_RECEIVER, $response->getMessage() );
	}

	public function testReceiverAddressNotGiven_returnsFailureResponse(): void {
		$response = $this->makeVerificationService( new Client() )->validate( [] );

		$this->assertEquals( PayPalVerificationService::ERROR_WRONG_RECEIVER, $response->getMessage() );
	}

	public function testPaymentStatusNotConfirmable_returnsFailureResponse(): void {
		$response = $this->makeVerificationService( new Client() )->validate( [
			'receiver_email' => self::VALID_ACCOUNT_EMAIL,
			'payment_status' => self::INVALID_PAYMENT_STATUS,
			'mc_currency' => 'EUR'
		] );

		$expectedMessage = sprintf( PayPalVerificationService::ERROR_UNKNOWN, '' );
		$this->assertStringStartsWith( $expectedMessage, $response->getMessage() );
	}

	public function testReturnsSuccessResponse(): void {
		$response = $this->makeVerificationService( $this->makeSucceedingClient() )
			->validate( $this->makeRequest() );

		$this->assertTrue( $response->isValid() );
	}

	public function testAlternateCurrencyField_returnsSuccessResponse(): void {
		$response = $this->makeVerificationService( $this->makeSucceedingClient() )
			->validate( [
				'receiver_email' => self::VALID_ACCOUNT_EMAIL,
				'payment_status' => self::VALID_PAYMENT_STATUS,
				'item_name' => self::ITEM_NAME,
				'currency_code' => self::CURRENCY_EUR,
				'txn_type' => 'recurring_payment_suspended_due_to_max_failed_payment',
			] );

		$this->assertTrue( $response->isValid() );
	}

	public function testPaypalHttpCallReturnsUnconfirmedPayment_returnsFailureResponse(): void {
		$response = $this->makeVerificationService( $this->makeClientWithErrorResponse() )
			->validate( $this->makeRequest() );

		$this->assertEquals( PayPalVerificationService::ERROR_UNCONFIRMED, $response->getMessage() );
	}

	public function testPaypalHttpCallFails_returnsFailureResponse(): void {
		$response = $this->makeVerificationService( $this->makeFailingClient() )
			->validate( $this->makeRequest() );

		$messageStart = sprintf( PayPalVerificationService::ERROR_HTTP_ERROR, '' );
		$this->assertStringStartsWith( $messageStart, $response->getMessage() );
	}

	public function testPaypalHttpCallReturnsUnexpectedResponse_returnsFailureResponse(): void {
		$response = $this->makeVerificationService( $this->newClient( 'Ra-ra-rasputin, lover of the Russian queen!' ) )
			->validate( $this->makeRequest() );

		$messageStart = sprintf( PayPalVerificationService::ERROR_UNKNOWN, '' );
		$this->assertStringStartsWith( $messageStart, $response->getMessage() );
	}

	public function testGivenRecurringPaymentStatusMessage_currencyIsCheckedInDifferentField(): void {
		$expectedParams = [
			'cmd' => '_notify-validate',
			'receiver_email' => self::VALID_ACCOUNT_EMAIL,
			'payment_status' => self::VALID_PAYMENT_STATUS,
			'item_name' => self::ITEM_NAME,
			'txn_type' => self::RECURRING_NO_PAYMENT,
			'currency_code' => self::CURRENCY_EUR
		];
		$this->makeVerificationService( $this->makeSucceedingClientExpectingParams( $expectedParams ) )
			->validate( $this->makeFailedRecurringPaymentRequest() );
		$this->assertVerificationParametersWereSent();
	}

	/**
	 * @return array<string,string>
	 */
	private function makeRequest(): array {
		return [
			'receiver_email' => self::VALID_ACCOUNT_EMAIL,
			'payment_status' => self::VALID_PAYMENT_STATUS,
			'item_name' => self::ITEM_NAME,
			'mc_currency' => self::CURRENCY_EUR
		];
	}

	/**
	 * @return array<string,string>
	 */
	private function makeFailedRecurringPaymentRequest(): array {
		return [
			'receiver_email' => self::VALID_ACCOUNT_EMAIL,
			'payment_status' => self::VALID_PAYMENT_STATUS,
			'item_name' => self::ITEM_NAME,
			'txn_type' => self::RECURRING_NO_PAYMENT,
			'currency_code' => self::CURRENCY_EUR
		];
	}

	private function makeVerificationService( Client $httpClient ): PayPalVerificationService {
		return new PayPalVerificationService(
			$httpClient,
			self::DUMMY_API_URL,
			self::VALID_ACCOUNT_EMAIL
		);
	}

	private function makeSucceedingClient(): Client {
		return $this->newClient( 'VERIFIED' );
	}

	/**
	 * @param array<string,mixed> $expectedParams
	 *
	 * @return Client
	 */
	private function makeSucceedingClientExpectingParams( array $expectedParams ): Client {
		return $this->newClient( 'VERIFIED', $expectedParams );
	}

	private function makeClientWithErrorResponse(): Client {
		return $this->newClient( 'INVALID' );
	}

	/**
	 * @param string $body
	 * @param array<string,mixed> $expectedParams
	 *
	 * @return Client
	 */
	private function newClient( string $body, array $expectedParams = [] ): Client {
		$this->receivedRequests = [];
		$history = Middleware::history( $this->receivedRequests );
		$mock = new MockHandler( [
			new Response( 200, [], $body )
		] );
		$handlerStack = HandlerStack::create( $mock );
		$handlerStack->push( $history );
		$this->expectedRequestParameters = $expectedParams;
		return new Client( [ 'handler' => $handlerStack ] );
	}

	private function makeFailingClient(): Client {
		$mock = new MockHandler( [
			new Response( 500, [], 'Internal Server Error - Paypal is overwhelmed' )
		] );
		$handlerStack = HandlerStack::create( $mock );
		return new Client( [ 'handler' => $handlerStack ] );
	}

	private function assertVerificationParametersWereSent(): void {
		if ( count( $this->expectedRequestParameters ) == 0 ) {
			return;
		}
		if ( count( $this->receivedRequests ) === 0 ) {
			$this->fail( 'No verification requests received' );
		}
		/** @var \GuzzleHttp\Psr7\Request $req */
		$req = $this->receivedRequests[0]['request'];
		parse_str( $req->getBody()->getContents(), $receivedArguments );

		$this->assertEquals( $this->expectedRequestParameters, $receivedArguments );
	}

}
