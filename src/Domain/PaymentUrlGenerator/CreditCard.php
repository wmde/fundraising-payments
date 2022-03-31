<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class CreditCard implements PaymentProviderURLGenerator {

	private CreditCardConfig $config;
	private AdditionalPaymentData $additionalPaymentData;

	public function __construct( CreditCardConfig $config, AdditionalPaymentData $additionalPaymentData ) {
		$this->config = $config;
		$this->additionalPaymentData = $additionalPaymentData;
	}

	public function generateUrl( RequestContext $requestContext ): string {
		$baseUrl = $this->config->getBaseUrl();
		$params = [
			'project' => $this->config->getProjectId(),
			'bgcolor' => $this->config->getBackgroundColor(),
			'paytext' => $this->config->getTranslatableDescription()->getText( $this->additionalPaymentData ),
			'mp_user_firstname' => $requestContext->firstName,
			'mp_user_surname' => $requestContext->lastName,
			'sid' => $requestContext->itemId,
			'gfx' => $this->config->getLogo(),
			'token' => $requestContext->accessToken,
			'utoken' => $requestContext->updateToken,
			'amount' => $this->additionalPaymentData->amount->getEuroCents(),
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
