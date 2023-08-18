<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\UrlGenerator;

class RequestContext {

	/**
	 * @param int $itemId (internal) donation ID or membership ID
	 * @param string $invoiceId unique, currently derived from donation/membership ID
	 * @param string $firstName
	 * @param string $lastName
	 */
	public function __construct(
		public readonly int $itemId,
		public readonly string $invoiceId = '',
		public readonly string $firstName = '',
		public readonly string $lastName = ''
	) {
	}

}
