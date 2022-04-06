<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use RuntimeException;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\Request;
use WMDE\Fundraising\PaymentContext\DataAccess\Sofort\Transfer\SofortClient;
use WMDE\Fundraising\PaymentContext\Domain\Model\Payment;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;

class Sofort implements PaymentProviderURLGenerator {

	private const CURRENCY = 'EUR';

	private SofortConfig $config;
	private SofortClient $client;
	private SofortPayment $payment;

	public function __construct(
			SofortConfig $config,
			SofortClient $client,
			Payment|SofortPayment $payment ) {
		if ( !$payment instanceof SofortPayment ) {
			throw new \LogicException( "Must be of type SofortPayment, you provided: " . get_class( $payment ) );
		}

		$this->config = $config;
		$this->client = $client;
		$this->payment = $payment;
	}

	public function generateUrl( RequestContext $requestContext ): string {
		$request = new Request();
		$request->setAmount( $this->payment->getAmount() );
		$request->setCurrencyCode( self::CURRENCY );
		$request->setReasons( [ $this->config->getReasonText(), $this->payment->getPaymentReferenceCode() ] );
		$request->setSuccessUrl(
			$this->config->getReturnUrl() . '?' . http_build_query(
				[
					'id' => $requestContext->itemId,
					'accessToken' => $requestContext->accessToken
				]
			)
		);
		$request->setAbortUrl( $this->config->getCancelUrl() );
		$request->setNotificationUrl(
			$this->config->getNotificationUrl() . '?' . http_build_query(
				[
					'id' => $requestContext->itemId,
					'updateToken' => $requestContext->updateToken
				]
			)
		);
		$request->setLocale( $this->config->getLocale() );

		try {
			$response = $this->client->get( $request );
		}
		catch ( RuntimeException $exception ) {
			throw new RuntimeException( 'Could not generate Sofort URL: ' . $exception->getMessage() );
		}

		return $response->getPaymentUrl();
	}
}
