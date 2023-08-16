<?php
declare( strict_types=1 );

namespace WMDE\Fundraising\PaymentContext\UseCases\CreatePayment;

use DateTimeImmutable;
use WMDE\Fundraising\PaymentContext\Domain\UrlGenerator\RequestContext;

class DomainSpecificContext {
	/**
	 * @param int $itemId (internal) donation ID or membership ID
	 * @param string $userAccessToken A token used by our URL generators to grant access to the item.
	 *         It may consist of multiple tokens (access and update token), concatenated with a colon.
	 *         See https://phabricator.wikimedia.org/T344346 for our plans to use a single token for user access.
	 * @param string $systemAccessToken A token used by our URL generators when creating a URL for server notification
	 *          end point (where an external payment provider confirms a payment)
	 * @param DateTimeImmutable|null $startTimeForRecurringPayment
	 * @param string $invoiceId unique, currently derived from donation/membership ID
	 * @param string $firstName
	 * @param string $lastName
	 */
	public function __construct(
		public readonly int $itemId,
		public readonly string $userAccessToken,
		public readonly string $systemAccessToken,

		public readonly ?DateTimeImmutable $startTimeForRecurringPayment = null,

		public readonly string $invoiceId = '',
		public readonly string $firstName = '',
		public readonly string $lastName = ''
	) {
	}

	public function getRequestContextForUrlGenerator(): RequestContext {
		// TODO: remove branching condition once we have implemented user and system access tokens,
		//       see https://phabricator.wikimedia.org/T344346
		if ( str_contains( $this->userAccessToken, ':' ) ) {
			$tokens = $this->getLegacyTokens();
		} else {
			$tokens = [
				'accessToken' => $this->userAccessToken,
				'updateToken' => $this->systemAccessToken
			];
		}
		return new RequestContext(
			$this->itemId,
			$this->invoiceId,
			$tokens['updateToken'],
			$tokens['accessToken'],
			$this->firstName,
			$this->lastName
		);
	}

	/**
	 * @return array{accessToken:string,updateToken:string}
	 * @deprecated see https://phabricator.wikimedia.org/T344346
	 */
	public function getLegacyTokens(): array {
		$tokens = explode( ':', $this->userAccessToken, 2 );
		return [
			'accessToken' => $tokens[0],
			'updateToken' => $tokens[1] ?? ''
		];
	}

}
