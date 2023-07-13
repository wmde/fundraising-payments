<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\PayPal;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationResponse;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationService;

/**
 * This class checks some basic properties of a PayPal IPN message
 * and sends it back to PayPal to make sure it originated from PayPal.
 *
 * See https://developer.paypal.com/docs/api-basics/notifications/ipn/IPNImplementation/#specs
 */
class PayPalVerificationService implements VerificationService {

	public const ERROR_WRONG_RECEIVER = 'Payment receiver address does not match';
	public const ERROR_UNSUPPORTED_CURRENCY = 'Unsupported currency';
	public const ERROR_HTTP_ERROR = 'Payment provider returned an error. HTTP status: %s';
	public const ERROR_UNCONFIRMED = 'Payment provider did not confirm the sent data';
	public const ERROR_UNKNOWN = 'An error occurred while trying to confirm the sent data. PayPal response: %s';

	private const ALLOWED_CURRENCY_CODES = [ 'EUR' ];
	private const NOTIFICATION_TYPES_WITH_DIFFERENT_CURRENCY_FIELDS = [
		'recurring_payment_suspended_due_to_max_failed_payment'
	];

	private Client $httpClient;

	/**
	 * @var string PayPal IPN verification end point
	 */
	private string $baseUrl;

	/**
	 * @var string Email address of our PayPal account
	 * @todo Convert to array and remove phpstan-ignore when we allow multiple receivers
	 * @phpstan-ignore-next-line
	 */
	private string $accountEmailAddress;

	public function __construct( Client $httpClient, string $baseUrl, string $accountEmailAddress ) {
		$this->httpClient = $httpClient;
		$this->baseUrl = $baseUrl;
		$this->accountEmailAddress = $accountEmailAddress;
	}

	/**
	 * @param array<string,mixed> $request
	 *
	 * @return VerificationResponse
	 */
	public function validate( array $request ): VerificationResponse {
		if ( !$this->matchesReceiverAddress( $request ) ) {
			return VerificationResponse::newFailureResponse( self::ERROR_WRONG_RECEIVER );
		}

		if ( !$this->hasValidCurrencyCode( $request ) ) {
			return VerificationResponse::newFailureResponse( self::ERROR_UNSUPPORTED_CURRENCY );
		}

		try {
			$result = $this->httpClient->request(
				'POST',
				$this->baseUrl,
				[
					RequestOptions::FORM_PARAMS => array_merge( [ 'cmd' => '_notify-validate' ], $request ),
					// disable throwing exceptions, return status instead
					RequestOptions::HTTP_ERRORS => false
				]
			);
		} catch ( GuzzleException $e ) {
			return VerificationResponse::newFailureResponse( sprintf( self::ERROR_UNKNOWN, $e->getMessage() ) );
		}

		if ( $result->getStatusCode() !== 200 ) {
			return VerificationResponse::newFailureResponse( sprintf( self::ERROR_HTTP_ERROR, $result->getStatusCode() ) );
		}

		$responseBody = trim( $result->getBody()->getContents() );
		if ( $responseBody === 'VERIFIED' ) {
			return VerificationResponse::newSuccessResponse();
		}

		$failureMessage = $responseBody === 'INVALID' ?
			self::ERROR_UNCONFIRMED :
			sprintf( self::ERROR_UNKNOWN, $responseBody );

		return VerificationResponse::newFailureResponse( $failureMessage );
	}

	/**
	 * @param array<string,mixed> $request
	 *
	 * @return bool
	 */
	private function matchesReceiverAddress( array $request ): bool {
		return true;
		// TODO allow for multiple receivers for legacy recurring payments
		// return array_key_exists( 'receiver_email', $request ) &&
		//	$request['receiver_email'] === $this->accountEmailAddress;
	}

	/**
	 * @param array<string,mixed> $request
	 *
	 * @return bool
	 */
	private function hasValidCurrencyCode( array $request ): bool {
		if ( $this->hasDifferentCurrencyField( $request ) ) {
			return array_key_exists( 'currency_code', $request ) &&
				in_array( $request['currency_code'], self::ALLOWED_CURRENCY_CODES );
		}
		return array_key_exists( 'mc_currency', $request ) &&
			in_array( $request['mc_currency'], self::ALLOWED_CURRENCY_CODES );
	}

	/**
	 * @param array<string,mixed> $request
	 *
	 * @return bool
	 */
	private function hasDifferentCurrencyField( array $request ): bool {
		return array_key_exists( 'txn_type', $request ) &&
			in_array( $request['txn_type'], self::NOTIFICATION_TYPES_WITH_DIFFERENT_CURRENCY_FIELDS );
	}

}
