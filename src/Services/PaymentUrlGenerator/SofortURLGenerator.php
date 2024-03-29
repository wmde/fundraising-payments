<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use RuntimeException;
use WMDE\Fundraising\PaymentContext\Domain\Model\SofortPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\DomainSpecificContext;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentCompletionURLGenerator;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\Request;
use WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator\Sofort\SofortClient;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

class SofortURLGenerator implements PaymentCompletionURLGenerator {

	private const CURRENCY = 'EUR';

	public function __construct(
		private readonly SofortURLGeneratorConfig $config,
		private readonly SofortClient $client,
		private readonly URLAuthenticator $authenticator,
		private readonly SofortPayment $payment
	) {
	}

	public function generateUrl( DomainSpecificContext $requestContext ): string {
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
			$this->config->getNotificationUrl() . '?' . http_build_query(
				$this->authenticator->getAuthenticationTokensForPaymentProviderUrl(
					self::class,
					[ 'id', 'updateToken' ]
				)
			)
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
