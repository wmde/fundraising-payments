<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\Services\PayPal\Model;

use DateTimeImmutable;

class Subscription {
	public function __construct(
		public readonly string $id,
		public readonly DateTimeImmutable $subscriptionStart,
		public readonly string $confirmationLink
	) {
	}

	/**
	 * @param array<string,mixed> $apiResponse The PayPal API response for "create subscription"
	 * @return self
	 */
	public static function from( array $apiResponse ): self {
		if ( !isset( $apiResponse['id'] ) || !isset( $apiResponse['start_time'] ) ) {
			throw new PayPalAPIException( 'Fields "id" and "start_time" are required' );
		}

		if ( !is_string( $apiResponse['id'] ) ) {
			throw new PayPalAPIException( "Id is not a valid string!" );
		}

		if ( !is_string( $apiResponse['start_time'] ) || $apiResponse['start_time'] === '' ) {
			throw new PayPalAPIException( 'Malformed date formate for start_time' );
		}

		try {
			$subscriptionStart = new DateTimeImmutable( $apiResponse['start_time'] );
		} catch ( \Exception $e ) {
			throw new PayPalAPIException( 'Malformed date formate for start_time', 0, $e );
		}

		if ( !isset( $apiResponse['links'] ) || !is_array( $apiResponse['links'] ) ) {
			throw new PayPalAPIException( 'Fields must contain array with links!' );
		}

		$url = self::getUrlFromLinks( $apiResponse['links'] );

		return new Subscription( $apiResponse['id'], $subscriptionStart, $url );
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
		throw new PayPalAPIException( 'Link array did not contain approval link!' );
	}
}
