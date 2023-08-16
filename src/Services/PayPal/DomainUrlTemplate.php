<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal;

use WMDE\Fundraising\PaymentContext\UseCases\CreatePayment\DomainSpecificContext;

class DomainUrlTemplate {
	/**
	 * @var array<string,string>
	 */
	private array $context;

	public function __construct( DomainSpecificContext $context ) {
		$this->context = [
			'id' => (string)$context->itemId,
			'userAccessToken' => $context->userAccessToken,
			'systemAccessToken' => $context->systemAccessToken,
		];

		// Only needed until we actually use the user and system access tokens, see https://phabricator.wikimedia.org/T344346
		if ( str_contains( $context->userAccessToken, ':' ) ) {
			$this->context = array_merge(
				$context->getLegacyTokens(),
				$this->context
			);
		}
	}

	public function replacePlaceholders( string $url ): string {
		return str_replace(
			array_map( fn( $key ) => '{{' . $key . '}}', array_keys( $this->context ) ),
			array_values( $this->context ),
			$url
		);
	}
}
