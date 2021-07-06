<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

use WMDE\Euro\Euro;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 */
class CreditCard {

	private $config;

	public function __construct( CreditCardConfig $config ) {
		$this->config = $config;
	}

	public function generateUrl( string $firstName, string $lastName, string $payText, int $donationId,
		string $accessToken, string $updateToken, Euro $amount ): string {
		$baseUrl = $this->config->getBaseUrl();
		$params = [
			'project' => $this->config->getProjectId(),
			'bgcolor' => $this->config->getBackgroundColor(),
			'paytext' => $payText,
			'mp_user_firstname' => $firstName,
			'mp_user_surname' => $lastName,
			'sid' => $donationId,
			'gfx' => $this->config->getLogo(),
			'token' => $accessToken,
			'utoken' => $updateToken,
			'amount' => $amount->getEuroCents(),
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
