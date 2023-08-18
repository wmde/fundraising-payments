<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Services\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\PaymentProviderURLGenerator;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;
use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

class CreditCardURLGenerator implements PaymentProviderURLGenerator {

	public function __construct(
		private readonly CreditCardURLGeneratorConfig $config,
		private readonly URLAuthenticator $urlAuthenticator,
		private readonly CreditCardPayment $payment,
	) {
	}

	public function generateUrl( RequestContext $requestContext ): string {
		$baseUrl = $this->config->getBaseUrl();
		$params = [
			'project' => $this->config->getProjectId(),
			'bgcolor' => $this->config->getBackgroundColor(),
			'paytext' => $this->config->getTranslatableDescription()->getText( $this->payment->getAmount(), $this->payment->getInterval() ),
			'mp_user_firstname' => $requestContext->firstName,
			'mp_user_surname' => $requestContext->lastName,
			'sid' => $requestContext->itemId,
			'gfx' => $this->config->getLogo(),
			'amount' => $this->payment->getAmount()->getEuroCents(),
			'theme' => $this->config->getTheme(),
			'producttype' => 'fee',
			'lang' => $this->config->getLocale(),
			...$this->urlAuthenticator->getAuthenticationTokensForPaymentProviderUrl( self::class, [ 'token', 'utoken' ] )
		];
		if ( $this->config->isTestMode() ) {
			$params['testmode'] = '1';
		}

		return $baseUrl . http_build_query( $params );
	}

}
