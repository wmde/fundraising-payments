<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Tests\Fixtures;

use WMDE\Fundraising\PaymentContext\Services\URLAuthenticator;

class FakeUrlAuthenticator implements URLAuthenticator {
	public const TOKEN_PARAM = 'testAccessToken=LET_ME_IN';
	public const PAYMENT_PROVIDER_PARAM_PREFIX = 'p-test-param-';

	public function addAuthenticationTokensToApplicationUrl( string $url ): string {
		$urlParts = parse_url( $url );
		if ( !is_array( $urlParts ) ) {
			return $url;
		}
		$query = $urlParts['query'] ?? '';
		$urlParts['query'] = $query ? "$query&" . self::TOKEN_PARAM : self::TOKEN_PARAM;
		return $this->buildUrl( $urlParts );
	}

	/**
	 * @param array<string,int|string> $urlParts
	 * @return string
	 */
	private function buildUrl( array $urlParts ): string {
		$scheme = isset( $urlParts['scheme'] ) ? $urlParts['scheme'] . '://' : '';

		$host = $urlParts['host'] ?? '';

		$path = $urlParts['path'] ?? '';

		$query = isset( $urlParts['query'] ) ? '?' . $urlParts['query'] : '';

		$fragment = isset( $urlParts['fragment'] ) ? '#' . $urlParts['fragment'] : '';

		return "$scheme$host$path$query$fragment";
	}

	public function getAuthenticationTokensForPaymentProviderUrl( string $urlGeneratorClass, array $requestedParameters ): array {
		$resultParameters = [];
		foreach ( $requestedParameters as $idx => $parameter ) {
			$resultParameters[$parameter] = self::PAYMENT_PROVIDER_PARAM_PREFIX . $idx;
		}
		return $resultParameters;
	}
}
