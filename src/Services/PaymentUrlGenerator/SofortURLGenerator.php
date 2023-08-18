<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use RuntimeException;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\Request;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\SofortClient;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

class SofortURLGenerator implements PaymentProviderURLGenerator {

	private const CURRENCY = 'EUR';

	public function __construct(
		private readonly SofortURLGeneratorConfig $config,
		private readonly SofortClient $client,
		private readonly URLAuthenticator $authenticator,
		private readonly SofortPayment $payment
	) {
	}

	public function generateUrl( RequestContext $requestContext ): string {
		$request = new Request();
		$request->setAmount( $this->payment->getAmount() );
		$request->setCurrencyCode( self::CURRENCY );
		$request->setReasons( [
			$this->config->getTranslatableDescription()->getText(
				$this->payment->getAmount(),
				$this->payment->getInterval()
			),
			$this->payment->getPaymentReferenceCode()
		] );
		$request->setSuccessUrl(
			$this->authenticator->addAuthenticationTokensToApplicationUrl( $this->config->getReturnUrl() )
		);
		$request->setAbortUrl( $this->config->getCancelUrl() );
		$request->setNotificationUrl(
			$this->authenticator->addAuthenticationTokensToApplicationUrl( $this->config->getNotificationUrl() )
		);
		$request->setLocale( $this->config->getLocale() );

		try {
			$response = $this->client->get( $request );
		} catch ( RuntimeException $exception ) {
			throw new RuntimeException( 'Could not generate Sofort URL: ' . $exception->getMessage() );
		}

		return $response->getPaymentUrl();
	}
}
