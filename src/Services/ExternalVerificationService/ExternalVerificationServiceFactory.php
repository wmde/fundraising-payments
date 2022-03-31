<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService;

use GuzzleHttp\Client;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\PayPalPayment;
use WMDE\Fundraising\PaymentContext\Services\ExternalVerificationService\PayPal\PayPalVerificationService;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationService;
use WMDE\Fundraising\PaymentContext\UseCases\BookPayment\VerificationServiceFactory;

class ExternalVerificationServiceFactory implements VerificationServiceFactory {

	private Client $httpClient;

	/**
	 * @var string PayPal IPN verification end point
	 */
	private string $payPalBaseUrl;

	/**
	 * @var string Email address of our PayPal account
	 */
	private string $payPalAccountEmailAddress;

	public function __construct( Client $httpClient, string $payPalBaseUrl, string $payPalAccountEmailAddress ) {
		$this->httpClient = $httpClient;
		$this->payPalBaseUrl = $payPalBaseUrl;
		$this->payPalAccountEmailAddress = $payPalAccountEmailAddress;
	}

	public function create( Payment $payment ): VerificationService {
		if ( get_class( $payment ) == PayPalPayment::class ) {
			return $this->newPayPalVerificationService();
		} else {
			return $this->newSucceedingVerificationService();
		}
	}

	private function newPayPalVerificationService(): VerificationService {
		return new PayPalVerificationService(
			$this->httpClient,
			$this->payPalBaseUrl,
			$this->payPalAccountEmailAddress
		);
	}

	private function newSucceedingVerificationService(): VerificationService {
		return new SucceedingVerificationService();
	}
}
