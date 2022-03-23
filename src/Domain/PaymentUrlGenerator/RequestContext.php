<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\PaymentUrlGenerator;

class RequestContext {

	/**
	 * @param int $itemId (internal) donation ID or membership ID
	 * @param string $invoiceId unique, currently derived from donation/membership ID
	 * @param string $updateToken A token to use to invoke our API to change payment details at a later point in time
	 * @param string $accessToken A token to use to return to the payment process after completing the 3rd party process
	 * @param string $firstName
	 * @param string $lastName
	 */
	public function __construct(
		public readonly int $itemId,
		public readonly string $invoiceId = '',
		public readonly string $updateToken = '',
		public readonly string $accessToken = '',

		public readonly string $firstName = '',
		public readonly string $lastName = ''
	) {
	}

}
