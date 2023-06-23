<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Fundraising\PaymentContext\Domain\Model\CreditCardPayment;

class CreditCardURLGenerator implements PaymentProviderURLGenerator {

	private CreditCardURLGeneratorConfig $config;
	private CreditCardPayment $payment;

	public function __construct( CreditCardURLGeneratorConfig $config, CreditCardPayment $payment ) {
		$this->config = $config;
		$this->payment = $payment;
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
			'token' => $requestContext->accessToken,
			'utoken' => $requestContext->updateToken,
			'amount' => $this->payment->getAmount()->getEuroCents(),
			'theme' => $this->config->getTheme(),
			'producttype' => 'fee',
			'lang' => $this->config->getLocale(),
		];
		if ( $this->config->isTestMode() ) {
			$params['testmode'] = '1';
		}

		return $baseUrl . http_build_query( $params );
	}

}
