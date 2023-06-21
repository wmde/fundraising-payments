<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

use WMDE\Fundraising\PaymentContext\Services\PayPal\PayPalAPIException;

class Order {

	public function __construct(
		public readonly string $id,
		public readonly string $confirmationLink
	) {
	}

	/**
	 * @param array<string,mixed> $apiResponse The PayPal API response for "create Order"
	 * @return self
	 */
	public static function from( array $apiResponse ): self {
		if ( !isset( $apiResponse['id'] ) ) {
			throw new PayPalAPIException( 'Field "id" is required!' );
		}

		if ( !is_string( $apiResponse['id'] ) || $apiResponse['id'] === '' ) {
			throw new PayPalAPIException( "Id is not a valid string!" );
		}

		if ( !isset( $apiResponse['links'] ) || !is_array( $apiResponse['links'] ) ) {
			throw new PayPalAPIException( 'Fields must contain array with links!' );
		}

		return new self( $apiResponse['id'], self::getUrlFromLinks( $apiResponse['links'] ) );
	}

	/**
	 * @param array{"rel":string,"href":string}[] $links
	 * @return string
	 */
	private static function getUrlFromLinks( array $links ): string {
		foreach ( $links as $link ) {
			if ( $link['rel'] === 'approve' ) {
				return $link['href'];
			}
		}
		throw new PayPalAPIException( "Link array did not contain approve link!" );
	}
}
